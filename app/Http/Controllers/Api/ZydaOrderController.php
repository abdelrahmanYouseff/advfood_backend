<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderSyncService;
use App\Models\ZydaOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ZydaOrderController extends Controller
{
    public function __construct(protected OrderSyncService $orderSyncService)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'total_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
        ]);

        $exists = DB::table('zyda_orders')
            ->where('phone', $validated['phone'])
            ->exists();

        $payload = [
            'name' => $validated['name'] ?? null,
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'location' => $validated['location'] ?? null,
            'total_amount' => $validated['total_amount'] ?? 0,
            'items' => $validated['items'],
        ];

        $result = $this->orderSyncService->saveScrapedOrder($payload);

        if (!$result) {
            throw ValidationException::withMessages([
                'phone' => ['Failed to save Zyda order.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'operation' => $exists ? 'updated' : 'created',
        ]);
    }

    public function updateLocation(Request $request, $id)
    {
        $validated = $request->validate([
            'location' => 'nullable|string|max:500',
        ]);

        $zydaOrder = ZydaOrder::find($id);

        if (!$zydaOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Zyda order not found',
            ], 404);
        }

        // Parse location to extract latitude and longitude
        $coordinates = $this->parseLocation($validated['location']);

        // Update location, latitude, and longitude in zyda_order
        $zydaOrder->location = $validated['location'];
        if ($coordinates) {
            $zydaOrder->latitude = $coordinates['latitude'];
            $zydaOrder->longitude = $coordinates['longitude'];
        }
        $zydaOrder->save();

        // Check if order already exists for this zyda_order
        if ($zydaOrder->order_id) {
            $existingOrder = Order::find($zydaOrder->order_id);
            if ($existingOrder) {
                // Update existing order location if needed
                if ($coordinates) {
                    $existingOrder->customer_latitude = $coordinates['latitude'];
                    $existingOrder->customer_longitude = $coordinates['longitude'];
                    $existingOrder->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated successfully',
                    'data' => $zydaOrder,
                    'order_id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number,
                    'coordinates' => $coordinates,
                ]);
            }
        }

        // Create new Order from ZydaOrder if location is provided
        if (!empty($validated['location'])) {
            try {
                $createdOrder = $this->createOrderFromZydaOrder($zydaOrder);

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated and order created successfully',
                    'data' => $zydaOrder,
                    'order_id' => $createdOrder->id,
                    'order_number' => $createdOrder->order_number,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create order from ZydaOrder', [
                    'zyda_order_id' => $zydaOrder->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated successfully, but failed to create order: ' . $e->getMessage(),
                    'data' => $zydaOrder,
                ], 200);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $zydaOrder,
        ]);
    }

    /**
     * Delete a Zyda order
     */
    public function destroy($id)
    {
        $zydaOrder = ZydaOrder::find($id);

        if (!$zydaOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Zyda order not found',
            ], 404);
        }

        // Delete the zyda order
        $zydaOrder->delete();

        Log::info('✅ Zyda order deleted', [
            'zyda_order_id' => $id,
            'phone' => $zydaOrder->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zyda order deleted successfully',
        ]);
    }

    /**
     * Create Order from ZydaOrder
     */
    protected function createOrderFromZydaOrder(ZydaOrder $zydaOrder): Order
    {
        // Get first user and restaurant (similar to createTestOrder)
        $user = User::first();
        $restaurant = Restaurant::first();

        if (!$user || !$restaurant) {
            throw new \Exception('يجب وجود مستخدم ومطعم واحد على الأقل في قاعدة البيانات');
        }

        // Parse location to get coordinates
        $coordinates = $this->parseLocation($zydaOrder->location);
        
        // Get shop_id from restaurant
        $shopId = $restaurant->shop_id ?? (string) $restaurant->id;

        // Generate order number
        $orderNumber = 'ZYDA-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = (float) $zydaOrder->total_amount;
        $deliveryFee = $restaurant->delivery_fee ?? 10.00;
        $tax = $subtotal * 0.15; // 15% tax
        $total = $subtotal + $deliveryFee + $tax;

        // Create the order
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'shop_id' => $shopId,
            'status' => 'pending',
            'source' => 'zyda',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'tax' => $tax,
            'total' => $total,
            'delivery_address' => $zydaOrder->address ?? 'عنوان غير محدد',
            'delivery_phone' => $zydaOrder->phone,
            'delivery_name' => $zydaOrder->name ?? 'عميل',
            'customer_latitude' => $coordinates['latitude'] ?? null,
            'customer_longitude' => $coordinates['longitude'] ?? null,
            'payment_method' => 'cash',
            'payment_status' => 'paid', // Set as paid to show in orders list
            'sound' => true,
        ]);

        // Create order items from zyda_order items
        if (!empty($zydaOrder->items) && is_array($zydaOrder->items)) {
            $menuItem = MenuItem::where('restaurant_id', $restaurant->id)->first();
            
            foreach ($zydaOrder->items as $item) {
                // Try to get item details from zyda order item structure
                $itemName = $item['name'] ?? $item['item_name'] ?? 'منتج من زيدا';
                $itemPrice = isset($item['price']) ? (float) $item['price'] : ($subtotal / max(count($zydaOrder->items), 1));
                $itemQuantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem ? $menuItem->id : null,
                    'item_name' => $itemName,
                    'price' => $itemPrice,
                    'quantity' => $itemQuantity,
                    'subtotal' => $itemPrice * $itemQuantity,
                ]);
            }
        }

        // Update zyda_order with order_id
        $zydaOrder->order_id = $order->id;
        $zydaOrder->save();

        Log::info('✅ Order created from ZydaOrder', [
            'zyda_order_id' => $zydaOrder->id,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $order;
    }

    /**
     * Parse location string to extract latitude and longitude
     * Supports formats:
     * - "24.7136,46.6753" (comma-separated)
     * - {"lat": 24.7136, "lng": 46.6753} (JSON)
     * - {"latitude": 24.7136, "longitude": 46.6753} (JSON)
     * - Google Maps URLs (various formats)
     *   - https://www.google.com/maps?q=24.7136,46.6753
     *   - https://www.google.com/maps/@24.7136,46.6753,15z
     *   - https://www.google.com/maps/place/.../@24.7136,46.6753,15z
     *   - https://maps.google.com/?q=24.7136,46.6753
     *   - https://www.google.com/maps/dir/.../@24.7136,46.6753
     */
    protected function parseLocation(?string $location): ?array
    {
        if (empty($location)) {
            return null;
        }

        $trimmedLocation = trim($location);

        // Try to parse as JSON first
        $jsonData = json_decode($trimmedLocation, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            // Try different JSON formats
            if (isset($jsonData['lat']) && isset($jsonData['lng'])) {
                return [
                    'latitude' => (float) $jsonData['lat'],
                    'longitude' => (float) $jsonData['lng'],
                ];
            }
            if (isset($jsonData['latitude']) && isset($jsonData['longitude'])) {
                return [
                    'latitude' => (float) $jsonData['latitude'],
                    'longitude' => (float) $jsonData['longitude'],
                ];
            }
        }

        // Check if it's a URL (regular or short link)
        if (filter_var($trimmedLocation, FILTER_VALIDATE_URL)) {
            // Try to extract coordinates from URL (including following redirects for short links)
            $coordinates = $this->extractCoordinatesFromUrl($trimmedLocation);
            if ($coordinates) {
                return $coordinates;
            }
        }

        // Try to parse as "lat,lng" format (can be in URL or plain text)
        // Match coordinates in format: lat,lng (can have + or - signs, can be decimal)
        if (preg_match('/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $trimmedLocation, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            
            // Validate coordinates range (latitude: -90 to 90, longitude: -180 to 180)
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }
        }

        // If can't parse, return null
        Log::warning('Could not parse location string', ['location' => $location]);
        return null;
    }

    /**
     * Extract coordinates from Google Maps URL or similar mapping service URLs
     * Also handles short links (like is.gd, bit.ly, etc.) by following redirects
     */
    protected function extractCoordinatesFromUrl(string $url): ?array
    {
        // Google Maps formats:
        // https://www.google.com/maps?q=24.7136,46.6753
        // https://www.google.com/maps/@24.7136,46.6753,15z
        // https://www.google.com/maps/place/.../@24.7136,46.6753,15z
        // https://maps.google.com/?q=24.7136,46.6753
        // https://www.google.com/maps/dir/.../@24.7136,46.6753
        // Short links: https://is.gd/ZpIRU1, https://bit.ly/xxx, etc.
        
        $finalUrl = $url;
        
        // Check if it's a short link service (common short link domains)
        $shortLinkDomains = [
            'is.gd', 'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly',
            'short.link', 'tiny.cc', 'rebrand.ly', 'cutt.ly', 'buff.ly'
        ];
        
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        // Check if host is a known short link service
        $isShortLink = false;
        foreach ($shortLinkDomains as $domain) {
            if (strpos($host, $domain) !== false) {
                $isShortLink = true;
                break;
            }
        }
        
        // If it's a short link or doesn't look like Google Maps, follow redirects
        if ($isShortLink || (strpos($host, 'google.com') === false && strpos($host, 'maps') === false)) {
            try {
                Log::info('🔗 Following short link redirect', [
                    'original_url' => $url,
                    'host' => $host,
                ]);
                
                // Use cURL to get final URL after following redirects (more reliable)
                $finalUrl = $this->getFinalUrlFromRedirects($url);
                
                if ($finalUrl && $finalUrl !== $url) {
                    Log::info('✅ Short link resolved', [
                        'original_url' => $url,
                        'final_url' => $finalUrl,
                    ]);
                } else {
                    Log::warning('⚠️ Could not resolve short link', [
                        'original_url' => $url,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Error following short link redirect', [
                    'original_url' => $url,
                    'error' => $e->getMessage(),
                ]);
                // Continue with original URL if redirect fails
            }
        }
        
        // Parse final URL
        $parsedUrl = parse_url($finalUrl);
        
        // Extract from query parameters (q=lat,lng)
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            if (isset($queryParams['q'])) {
                $qValue = $queryParams['q'];
                if (preg_match('/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $qValue, $matches)) {
                    $lat = (float) $matches[1];
                    $lng = (float) $matches[2];
                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                        return [
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ];
                    }
                }
            }
        }

        // Extract from path (@lat,lng format)
        if (isset($parsedUrl['path'])) {
            // Match @lat,lng or @lat,lng,zoom
            if (preg_match('/@([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)(?:,(\d+)z)?/', $parsedUrl['path'], $matches)) {
                $lat = (float) $matches[1];
                $lng = (float) $matches[2];
                if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ];
                }
            }
        }

        // Try to find coordinates anywhere in the final URL
        if (preg_match('/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $finalUrl, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }
        }

        return null;
    }

    /**
     * Get final URL after following redirects using cURL
     */
    protected function getFinalUrlFromRedirects(string $url): string
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_exec($ch);
            
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($finalUrl && $httpCode < 400) {
                return $finalUrl;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get final URL from redirects', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $url;
    }
}

