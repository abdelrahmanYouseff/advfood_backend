<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    /**
     * Webhook URL to fetch location data from
     * Try API endpoint first, fallback to web page
     */
    protected string $webhookUrl = 'https://advfoodapp.clarastars.com/api/webhooks/logs';

    /**
     * Insert or update a scraped order in zyda_orders table.
     */
    public function saveScrapedOrder(array $orderData): bool
    {
        if (empty($orderData['phone'])) {
            return false;
        }

        // Check if zyda_order_key is provided (required for unique identification)
        $rawZydaOrderKey = $orderData['zyda_order_key'] ?? null;
        // Normalize: remove leading '#' and whitespace so it is stored without '#'
        $zydaOrderKey = $rawZydaOrderKey !== null
            ? ltrim((string) $rawZydaOrderKey, "# \t\n\r\0\x0B")
            : null;
        
        if (empty($zydaOrderKey)) {
            Log::error('❌ Zyda order key is required', [
                'phone' => $orderData['phone'] ?? null,
            ]);
            return false;
        }

        // Try to get location from whatsapp_msg table using unique Zyda order key
        // الفكرة: لما ييجي اوردر جديد من Zyda، ندور على نفس الكود الفريد في جدول whatsapp_msg
        // ولو لقينا سطر مطابق، ناخد الـ location منه ونحطه في zyda_orders
        $whatsappLocation = DB::table('whatsapp_msg')
            ->whereNotNull('location')
            ->where(function ($query) use ($zydaOrderKey) {
                // Exact match or deliver_order text contains the key
                $query->where('deliver_order', $zydaOrderKey)
                      ->orWhere('deliver_order', 'like', '%' . $zydaOrderKey . '%');
            })
            ->orderByDesc('id')
            ->value('location');

        // النهائي: لو في لوكيشن من الواتساب نستخدمه، لو لأ نستخدم اللي جاي من Zyda (لو موجود)
        $finalLocation = $whatsappLocation ?? ($orderData['location'] ?? null);

        $payload = [
            'name' => $orderData['name'] ?? null,
            'address' => $orderData['address'] ?? null,
            // Prefer location from WhatsApp if available, otherwise use incoming location (if any)
            'location' => $finalLocation,
            'total_amount' => isset($orderData['total_amount']) ? (float) $orderData['total_amount'] : 0,
            'items' => isset($orderData['items']) ? json_encode($orderData['items']) : json_encode([]),
            'zyda_order_key' => $zydaOrderKey,
            'updated_at' => Carbon::now(),
        ];

        // Check if order exists using zyda_order_key (unique identifier from Zyda)
        // IMPORTANT: Only add order if it doesn't exist. If exists, skip (don't add duplicate)
        $existingOrder = DB::table('zyda_orders')
            ->where('zyda_order_key', $zydaOrderKey)
            ->first();
        
        $isNewRecord = !$existingOrder;
        $zydaOrderId = $existingOrder->id ?? null;
        
        if ($isNewRecord) {
            // New order: add it to database
            $payload['phone'] = $orderData['phone'];
            $payload['created_at'] = Carbon::now();
            $zydaOrderId = DB::table('zyda_orders')->insertGetId($payload);
            $result = $zydaOrderId > 0;
            
            Log::info('✅ New Zyda order added to database', [
                'phone' => $orderData['phone'],
                'name' => $orderData['name'] ?? null,
                'zyda_order_key' => $zydaOrderKey,
                'total_amount' => $payload['total_amount'],
                'location_from_whatsapp' => $whatsappLocation,
            ]);
        } else {
            // If order already exists and we now have location from WhatsApp, update it once
            if (!empty($whatsappLocation) && empty($existingOrder->location ?? null)) {
                DB::table('zyda_orders')
                    ->where('id', $existingOrder->id)
                    ->update([
                        'location' => $whatsappLocation,
                        'updated_at' => Carbon::now(),
                    ]);

                Log::info('✅ Zyda order location updated from whatsapp_msg', [
                    'zyda_order_id' => $existingOrder->id,
                    'zyda_order_key' => $zydaOrderKey,
                    'location_from_whatsapp' => $whatsappLocation,
                ]);
            }

            // Order already exists: skip (don't add duplicate)
            $result = true; // Return true to indicate "processed" (skipped)
            Log::info('ℹ️ Zyda order already exists in database, skipping (no duplicate)', [
                'phone' => $orderData['phone'],
                'zyda_order_key' => $zydaOrderKey,
                'zyda_order_id' => $existingOrder->id,
                'order_id' => $existingOrder->order_id ?? null,
            ]);
        }

        // After saving, try to fetch location from webhook
        if ($result) {
            // ما زلنا نحاول نجلب لوكيشن من الـ webhook القديم (للتوافق)
            $this->fetchLocationFromWebhook($orderData['phone']);

            // فقط عند إنشاء سجل جديد في zyda_orders + وجود لوكيشن (من الواتساب أو من Zyda):
            // 1) نمرر اللوكيشن النهائي إلى API /api/zyda/orders/{id}/location
            //    والذي بدوره ينقل الطلب من zyda_orders إلى جدول orders
            if ($isNewRecord && $zydaOrderId && !empty($finalLocation)) {
                try {
                    $baseUrl = config('app.url');
                    $endpoint = rtrim($baseUrl, '/') . '/api/zyda/orders/' . $zydaOrderId . '/location';

                    $response = Http::timeout(30)->patch($endpoint, [
                        'location' => $finalLocation,
                    ]);

                    Log::info('📡 Called Zyda updateLocation API after location saved (new record)', [
                        'zyda_order_id' => $zydaOrderId,
                        'zyda_order_key' => $zydaOrderKey,
                        'endpoint' => $endpoint,
                        'status' => $response->status(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to call Zyda updateLocation API after location saved (new record)', [
                        'zyda_order_id' => $zydaOrderId,
                        'zyda_order_key' => $zydaOrderKey,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /**
     * Fetch location data from webhook and update zyda_orders table
     */
    protected function fetchLocationFromWebhook(string $phone): void
    {
        try {
            Log::info('🔍 Searching for location in webhook', [
                'phone' => $phone,
                'webhook_url' => $this->webhookUrl,
            ]);

            // Try to get webhook data
            $response = Http::timeout(30)->get($this->webhookUrl);

            if (!$response->successful()) {
                Log::warning('⚠️ Failed to fetch webhook data', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return;
            }

            $webhookData = $response->json();
            
            // Check if response has webhooks array
            if (isset($webhookData['webhooks']) && is_array($webhookData['webhooks'])) {
                $location = $this->searchLocationByPhone($phone, $webhookData['webhooks']);
            } else {
                // Search for phone number in webhook data
                $location = $this->searchLocationByPhone($phone, $webhookData);
            }

            if ($location) {
                // Update location in zyda_orders table
                DB::table('zyda_orders')
                    ->where('phone', $phone)
                    ->update([
                        'location' => $location,
                        'updated_at' => Carbon::now(),
                    ]);

                Log::info('✅ Location updated from webhook', [
                    'phone' => $phone,
                    'location' => $location,
                ]);
            } else {
                Log::info('ℹ️ Phone number not found in webhook data', [
                    'phone' => $phone,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error fetching location from webhook', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Search for location by phone number in webhook data
     */
    protected function searchLocationByPhone(string $phone, $webhookData): ?string
    {
        if (empty($webhookData)) {
            return null;
        }

        // Normalize phone for comparison (remove spaces, dashes, etc.)
        $normalizedPhone = $this->normalizePhone($phone);

        // Try different data structures
        // Case 1: Array of webhooks
        if (isset($webhookData['webhooks']) && is_array($webhookData['webhooks'])) {
            foreach ($webhookData['webhooks'] as $webhook) {
                $foundLocation = $this->findLocationInWebhookItem($normalizedPhone, $webhook);
                if ($foundLocation) {
                    return $foundLocation;
                }
            }
        }

        // Case 2: Direct array
        if (is_array($webhookData)) {
            foreach ($webhookData as $item) {
                $foundLocation = $this->findLocationInWebhookItem($normalizedPhone, $item);
                if ($foundLocation) {
                    return $foundLocation;
                }
            }
        }

        // Case 3: Nested data structure
        if (isset($webhookData['data']) && is_array($webhookData['data'])) {
            $foundLocation = $this->searchLocationByPhone($phone, $webhookData['data']);
            if ($foundLocation) {
                return $foundLocation;
            }
        }

        return null;
    }

    /**
     * Find location in a single webhook item
     */
    protected function findLocationInWebhookItem(string $normalizedPhone, $item): ?string
    {
        if (!is_array($item)) {
            return null;
        }

        // Try to find phone in various possible fields
        $phoneFields = ['phone', 'phone_number', 'mobile', 'mobile_number', 'tel', 'telephone'];
        $locationFields = ['location', 'address', 'coordinates', 'lat_lng', 'latlng', 'latitude', 'longitude', 'lat', 'lng'];

        // Check direct fields first
        foreach ($phoneFields as $phoneField) {
            if (isset($item[$phoneField])) {
                $itemPhone = $this->normalizePhone((string) $item[$phoneField]);
                
                if ($itemPhone === $normalizedPhone) {
                    // Found matching phone, now find location
                    foreach ($locationFields as $locationField) {
                        if (isset($item[$locationField]) && !empty($item[$locationField])) {
                            return is_array($item[$locationField]) 
                                ? json_encode($item[$locationField]) 
                                : (string) $item[$locationField];
                        }
                    }
                    
                    // Check for lat and lng separately and combine them
                    if (isset($item['lat']) && isset($item['lng'])) {
                        return json_encode([
                            'lat' => $item['lat'],
                            'lng' => $item['lng']
                        ]);
                    }
                    
                    // Also check nested data structure
                    if (isset($item['data']) && is_array($item['data'])) {
                        foreach ($locationFields as $locationField) {
                            if (isset($item['data'][$locationField]) && !empty($item['data'][$locationField])) {
                                return is_array($item['data'][$locationField]) 
                                    ? json_encode($item['data'][$locationField]) 
                                    : (string) $item['data'][$locationField];
                            }
                        }
                        
                        // Check for lat and lng in data
                        if (isset($item['data']['lat']) && isset($item['data']['lng'])) {
                            return json_encode([
                                'lat' => $item['data']['lat'],
                                'lng' => $item['data']['lng']
                            ]);
                        }
                    }
                    
                    // Check all_data field (from webhook logs)
                    if (isset($item['all_data']) && is_array($item['all_data'])) {
                        foreach ($locationFields as $locationField) {
                            if (isset($item['all_data'][$locationField]) && !empty($item['all_data'][$locationField])) {
                                return is_array($item['all_data'][$locationField]) 
                                    ? json_encode($item['all_data'][$locationField]) 
                                    : (string) $item['all_data'][$locationField];
                            }
                        }
                    }
                }
            }
        }

        // Recursively search in nested structures
        foreach ($item as $key => $value) {
            if (is_array($value)) {
                $foundLocation = $this->findLocationInWebhookItem($normalizedPhone, $value);
                if ($foundLocation) {
                    return $foundLocation;
                }
            }
        }

        return null;
    }

    /**
     * Normalize phone number for comparison
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Remove leading + if present
        $phone = ltrim($phone, '+');
        
        // Remove leading zeros
        $phone = ltrim($phone, '0');
        
        return $phone;
    }
}

