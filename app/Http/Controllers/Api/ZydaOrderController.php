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
        try {
            Log::info('📥 Zyda order received via API', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload_keys' => array_keys($request->all()),
                'zyda_order_key' => $request->input('zyda_order_key'),
                'phone' => $request->input('phone'),
            ]);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'total_amount' => 'nullable|numeric|min:0',
                'items' => 'nullable|array',
                'zyda_order_key' => 'required|string|max:255', // Unique order identifier from Zyda
            ]);

            Log::info('✅ Validation passed', [
                'zyda_order_key' => $validated['zyda_order_key'],
                'phone' => $validated['phone'],
            ]);

            // Check if order exists using zyda_order_key (unique identifier from Zyda)
        $exists = DB::table('zyda_orders')
                ->where('zyda_order_key', $validated['zyda_order_key'])
            ->exists();

            // If order already exists, return success without processing (no duplicate)
            if ($exists) {
                Log::info('ℹ️ Zyda order already exists, skipping', [
                    'zyda_order_key' => $validated['zyda_order_key'],
                    'phone' => $validated['phone'],
                ]);
                
                return response()->json([
                    'success' => true,
                    'operation' => 'skipped',
                    'message' => 'Order already exists in database',
                ]);
            }

            Log::info('🔄 Saving new Zyda order', [
                'zyda_order_key' => $validated['zyda_order_key'],
                'phone' => $validated['phone'],
            ]);

        $payload = [
            'name' => $validated['name'] ?? null,
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'location' => $validated['location'] ?? null,
            'total_amount' => $validated['total_amount'] ?? 0,
            'items' => $validated['items'],
                'zyda_order_key' => $validated['zyda_order_key'],
        ];

        $result = $this->orderSyncService->saveScrapedOrder($payload);

        if (!$result) {
                Log::error('❌ Failed to save Zyda order in OrderSyncService', [
                    'phone' => $validated['phone'],
                    'zyda_order_key' => $validated['zyda_order_key'],
                    'payload' => $payload,
                ]);
            throw ValidationException::withMessages([
                'phone' => ['Failed to save Zyda order.'],
            ]);
        }

            Log::info('✅ New Zyda order saved successfully', [
                'phone' => $validated['phone'],
                'zyda_order_key' => $validated['zyda_order_key'],
                'total_amount' => $validated['total_amount'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'operation' => 'created',
                'message' => 'Order saved successfully',
            ]);
        } catch (ValidationException $e) {
            Log::error('❌ Validation error in Zyda order store', [
                'errors' => $e->errors(),
                'payload' => $request->all(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('❌ Unexpected error in Zyda order store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);
            throw $e;
        }
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

        // Parse location to extract latitude and longitude AND get final Google Maps URL
        $locationData = $this->parseLocationAndExtractUrl($validated['location']);

        // Update location with final Google Maps URL (not the short link)
        // And update latitude, longitude from extracted coordinates
        if ($locationData) {
            // Save final Google Maps URL in location column
            $zydaOrder->location = $locationData['final_url'] ?? $validated['location'];
            
            // Save coordinates if extracted
            if (isset($locationData['coordinates']) && $locationData['coordinates']) {
                $zydaOrder->latitude = $locationData['coordinates']['latitude'];
                $zydaOrder->longitude = $locationData['coordinates']['longitude'];
                
                Log::info('✅ Location and coordinates saved', [
                    'zyda_order_id' => $id,
                    'final_url' => $locationData['final_url'],
                    'latitude' => $locationData['coordinates']['latitude'],
                    'longitude' => $locationData['coordinates']['longitude'],
                ]);
            } else {
                Log::warning('⚠️ Could not extract coordinates from location', [
                    'zyda_order_id' => $id,
                    'location' => $validated['location'],
                ]);
            }
        } else {
            // If parsing failed, save original location as is
            $zydaOrder->location = $validated['location'];
        }
        
        // Note: status column was removed from zyda_orders table
        // Order status is now determined by order_id presence (null = pending, not null = received)
        
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
        $locationData = $this->parseLocationAndExtractUrl($zydaOrder->location);
        $coordinates = $locationData['coordinates'] ?? null;
        
        // Get shop_id from restaurant
        $shopId = $restaurant->shop_id ?? (string) $restaurant->id;

        // Generate order number
        $orderNumber = 'ZYDA-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        // Use the exact total_amount from Zyda order (no additional fees or tax)
        // Each order should have its own data only, as it comes from Zyda
        $totalAmount = (float) $zydaOrder->total_amount;

        // Create the order with exact amount from Zyda
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'shop_id' => $shopId,
            'status' => 'pending',
            'source' => 'zyda',
            'subtotal' => $totalAmount, // Use total from Zyda as subtotal
            'delivery_fee' => 0, // No additional delivery fee
            'tax' => 0, // No additional tax (already included in Zyda total)
            'total' => $totalAmount, // Use exact total from Zyda
            'delivery_address' => $zydaOrder->address ?? 'عنوان غير محدد',
            'delivery_phone' => $zydaOrder->phone,
            'delivery_name' => $zydaOrder->name ?? 'عميل',
            'customer_latitude' => $coordinates['latitude'] ?? null,
            'customer_longitude' => $coordinates['longitude'] ?? null,
            'payment_method' => 'cash',
            'payment_status' => 'paid', // Set as paid to show in orders list
            'sound' => true,
        ]);

        // Link zyda_order to the created order
        $zydaOrder->order_id = $order->id;
        $zydaOrder->save();

        // Create order items from zyda_order items
        if (!empty($zydaOrder->items)) {
            // Parse items (can be JSON string or array)
            $items = is_string($zydaOrder->items) ? json_decode($zydaOrder->items, true) : $zydaOrder->items;
            
            if (is_array($items) && count($items) > 0) {
                $menuItem = MenuItem::where('restaurant_id', $restaurant->id)->first();
                
                // Calculate total quantity for price distribution if needed
                $totalQuantity = 0;
                foreach ($items as $item) {
                    if (is_array($item)) {
                        $totalQuantity += isset($item['quantity']) ? (int) $item['quantity'] : 1;
                    } else {
                        // Handle old format: string like "2x Burger"
                        $totalQuantity += 1;
                    }
                }
                
                foreach ($items as $item) {
                    if (is_array($item)) {
                        // New structured format: {"name": "Burger", "quantity": 2, "price": 37.0}
                        $itemName = $item['name'] ?? $item['item_name'] ?? 'منتج من زيدا';
                        $itemQuantity = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                        
                        // IMPORTANT: Use exact price from Zyda if available, otherwise calculate proportionally
                        if (isset($item['price']) && $item['price'] !== null && $item['price'] > 0) {
                            // Use exact price from Zyda platform (as is, no modifications)
                            $itemPrice = (float) $item['price'];
                            Log::info('✅ Using exact price from Zyda', [
                                'item_name' => $itemName,
                                'quantity' => $itemQuantity,
                                'price' => $itemPrice,
                            ]);
                        } else {
                            // If price not available, calculate proportionally based on quantity
                            $itemPrice = $totalQuantity > 0 ? ($totalAmount / $totalQuantity) : ($totalAmount / count($items));
                            Log::warning('⚠️ Price not available from Zyda, calculating proportionally', [
                                'item_name' => $itemName,
                                'quantity' => $itemQuantity,
                                'calculated_price' => $itemPrice,
                            ]);
                        }
                    } else {
                        // Old format: string like "2x Burger"
                        // Parse it
                        $itemStr = (string) $item;
                        if (preg_match('/^(\d+)x\s*(.+)$/i', $itemStr, $matches)) {
                            $itemQuantity = (int) $matches[1];
                            $itemName = trim($matches[2]);
                            $itemPrice = $totalQuantity > 0 ? ($totalAmount / $totalQuantity) : ($totalAmount / count($items));
                        } else {
                            // Fallback
                            $itemName = $itemStr;
                            $itemQuantity = 1;
                            $itemPrice = $totalAmount / count($items);
                        }
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_item_id' => $menuItem ? $menuItem->id : null,
                        'item_name' => $itemName,
                        'price' => $itemPrice,
                        'quantity' => $itemQuantity,
                        'subtotal' => $itemPrice * $itemQuantity,
                    ]);
                    
                    Log::info('✅ Order item created', [
                        'order_id' => $order->id,
                        'item_name' => $itemName,
                        'quantity' => $itemQuantity,
                        'price' => $itemPrice,
                        'subtotal' => $itemPrice * $itemQuantity,
                    ]);
                }
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
     * Parse location string and extract both coordinates AND final Google Maps URL
     * Returns array with 'coordinates' and 'final_url'
     * 
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
     * - Short links (any short link that redirects to Google Maps)
     *   - https://zyda.co/o2CONfN
     *   - https://is.gd/xxx
     *   - etc.
     */
    protected function parseLocationAndExtractUrl(?string $location): ?array
    {
        if (empty($location)) {
            return null;
        }

        $trimmedLocation = trim($location);
        $result = [
            'final_url' => $trimmedLocation, // Default to original location
            'coordinates' => null,
        ];

        // Try to parse as JSON first
        $jsonData = json_decode($trimmedLocation, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            // Try different JSON formats
            if (isset($jsonData['lat']) && isset($jsonData['lng'])) {
                $result['coordinates'] = [
                    'latitude' => (float) $jsonData['lat'],
                    'longitude' => (float) $jsonData['lng'],
                ];
                // Generate Google Maps URL from coordinates
                $result['final_url'] = "https://www.google.com/maps?q={$jsonData['lat']},{$jsonData['lng']}";
                return $result;
            }
            if (isset($jsonData['latitude']) && isset($jsonData['longitude'])) {
                $result['coordinates'] = [
                    'latitude' => (float) $jsonData['latitude'],
                    'longitude' => (float) $jsonData['longitude'],
                ];
                // Generate Google Maps URL from coordinates
                $result['final_url'] = "https://www.google.com/maps?q={$jsonData['latitude']},{$jsonData['longitude']}";
                return $result;
            }
        }

        // Check if it's a URL (regular or short link)
        if (filter_var($trimmedLocation, FILTER_VALIDATE_URL)) {
            // ALWAYS try to extract coordinates from URL
            // This will follow redirects for ANY URL (short links or regular) to get Google Maps coordinates
            $urlData = $this->extractCoordinatesAndUrl($trimmedLocation);
            if ($urlData) {
                $result['coordinates'] = $urlData['coordinates'];
                $result['final_url'] = $urlData['final_url'] ?? $trimmedLocation;
                return $result;
            }
        }

        // Try to parse as "lat,lng" format (can be in URL or plain text)
        // Match coordinates in format: lat,lng (can have + or - signs, can be decimal)
        if (preg_match('/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $trimmedLocation, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            
            // Validate coordinates range (latitude: -90 to 90, longitude: -180 to 180)
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $result['coordinates'] = [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
                // Generate Google Maps URL from coordinates
                $result['final_url'] = "https://www.google.com/maps?q={$lat},{$lng}";
                return $result;
            }
        }

        // If can't parse, return original location
        Log::warning('Could not parse location string', ['location' => $location]);
        return $result;
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use parseLocationAndExtractUrl instead
     */
    protected function parseLocation(?string $location): ?array
    {
        $result = $this->parseLocationAndExtractUrl($location);
        return $result ? $result['coordinates'] : null;
    }

    /**
     * Extract coordinates AND final Google Maps URL from any URL
     * Handles short links (like zyda.co, is.gd, bit.ly, etc.) by following redirects
     * Returns array with 'coordinates' and 'final_url'
     */
    protected function extractCoordinatesAndUrl(string $url): ?array
    {
        $result = [
            'coordinates' => null,
            'final_url' => $url,
        ];
        
        // First, get the final URL after following redirects
        $finalUrl = $this->extractCoordinatesFromUrlInternal($url);
        
        if ($finalUrl && $finalUrl !== $url) {
            $result['final_url'] = $finalUrl;
        } else {
            $result['final_url'] = $url;
        }
        
        // Now extract coordinates from final URL
        $coordinates = $this->extractCoordinatesFromFinalUrl($result['final_url']);
        $result['coordinates'] = $coordinates;
        
        return $result['coordinates'] ? $result : null;
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use extractCoordinatesAndUrl instead
     */
    protected function extractCoordinatesFromUrl(string $url): ?array
    {
        $result = $this->extractCoordinatesAndUrl($url);
        return $result ? $result['coordinates'] : null;
    }
    
    /**
     * Internal method to get final URL after following redirects
     * Returns the final Google Maps URL after following all redirects
     */
    protected function extractCoordinatesFromUrlInternal(string $url): string
    {
        // Google Maps formats:
        // https://www.google.com/maps?q=24.7136,46.6753
        // https://www.google.com/maps/@24.7136,46.6753,15z
        // https://www.google.com/maps/place/.../@24.7136,46.6753,15z
        // https://maps.google.com/?q=24.7136,46.6753
        // https://www.google.com/maps/dir/.../@24.7136,46.6753
        // Short links: https://is.gd/ZpIRU1, https://bit.ly/xxx, https://zyda.co/o2CONfN, etc.
        
        $finalUrl = $url;
        
        // Check if it's a short link service (common short link domains)
        $shortLinkDomains = [
            'is.gd', 'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly',
            'short.link', 'tiny.cc', 'rebrand.ly', 'cutt.ly', 'buff.ly',
            'zyda.co' // Zyda short links that redirect to Google Maps
        ];
        
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        // Check if host is a known short link service
        $isShortLink = false;
        $isGoogleMaps = (strpos($host, 'google.com') !== false && strpos($host, 'maps') !== false) || 
                       (strpos($host, 'maps') !== false);
        
        foreach ($shortLinkDomains as $domain) {
            if (strpos($host, $domain) !== false) {
                $isShortLink = true;
                break;
            }
        }
        
        // ALWAYS follow redirects if:
        // 1. It's a short link (known or unknown)
        // 2. It's NOT already a Google Maps URL
        // 3. We want to extract coordinates from ANY URL that redirects to Google Maps
        if ($isShortLink || !$isGoogleMaps) {
            try {
                Log::info('🔗 Following URL redirects to get final Google Maps URL', [
                    'original_url' => $url,
                    'host' => $host,
                    'is_short_link' => $isShortLink,
                    'is_google_maps' => $isGoogleMaps,
                ]);
                
                // Use cURL to get final URL after following redirects (more reliable)
                $finalUrl = $this->getFinalUrlFromRedirects($url);
                
                if ($finalUrl && $finalUrl !== $url) {
                    Log::info('✅ URL redirect resolved to Google Maps', [
                        'original_url' => $url,
                        'final_url' => $finalUrl,
                    ]);
                } else {
                    Log::warning('⚠️ Could not resolve URL redirect', [
                        'original_url' => $url,
                    ]);
                    // Continue with original URL
                    $finalUrl = $url;
                }
            } catch (\Exception $e) {
                Log::error('❌ Error following URL redirect', [
                    'original_url' => $url,
                    'error' => $e->getMessage(),
                ]);
                // Continue with original URL if redirect fails
                $finalUrl = $url;
            }
        } else {
            // Already a Google Maps URL, use as is
            $finalUrl = $url;
        }
        
        return $finalUrl;
    }

    /**
     * Get final URL after following redirects using cURL
     * Supports short links like zyda.co, is.gd, bit.ly, etc.
     */
    protected function getFinalUrlFromRedirects(string $url): string
    {
        try {
            Log::info('🔗 Following redirects to get final URL', [
                'original_url' => $url,
            ]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only (faster)
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased timeout for slow redirects
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); // Some sites require user agent
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                Log::warning('⚠️ cURL error following redirects', [
                    'url' => $url,
                    'error' => $error,
                ]);
                return $url;
            }
            
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($finalUrl && $httpCode < 400 && $finalUrl !== $url) {
                Log::info('✅ Successfully followed redirects', [
                    'original_url' => $url,
                    'final_url' => $finalUrl,
                    'http_code' => $httpCode,
                ]);
                return $finalUrl;
            } elseif ($finalUrl && $finalUrl !== $url) {
                Log::warning('⚠️ Redirect followed but HTTP code indicates error', [
                    'original_url' => $url,
                    'final_url' => $finalUrl,
                    'http_code' => $httpCode,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception following redirects', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        
        return $url;
    }
}

