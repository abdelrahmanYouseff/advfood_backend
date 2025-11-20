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
        Log::info('📥 Processing location update', [
            'zyda_order_id' => $id,
            'location' => $validated['location'],
        ]);
        
        $locationData = $this->parseLocationAndExtractUrl($validated['location']);
        
        // Initialize coordinates variable
        $coordinates = null;

        // Update location with final Google Maps URL (not the short link)
        // And update latitude, longitude from extracted coordinates
        if ($locationData) {
            // Save final Google Maps URL in location column
            $zydaOrder->location = $locationData['final_url'] ?? $validated['location'];
            
            // Save coordinates if extracted
            if (isset($locationData['coordinates']) && $locationData['coordinates']) {
                $coordinates = $locationData['coordinates'];
                $zydaOrder->latitude = $coordinates['latitude'];
                $zydaOrder->longitude = $coordinates['longitude'];
                
                Log::info('✅ Location and coordinates saved', [
                    'zyda_order_id' => $id,
                    'final_url' => $locationData['final_url'],
                    'latitude' => $coordinates['latitude'],
                    'longitude' => $coordinates['longitude'],
                ]);
            } else {
                Log::warning('⚠️ Could not extract coordinates from location', [
                    'zyda_order_id' => $id,
                    'location' => $validated['location'],
                    'location_data' => $locationData,
                ]);
            }
        } else {
            // If parsing failed, save original location as is
            $zydaOrder->location = $validated['location'];
            
            Log::warning('⚠️ Location parsing returned null', [
                'zyda_order_id' => $id,
                'location' => $validated['location'],
            ]);
        }
        
        // Note: status column was removed from zyda_orders table
        // Order status is now determined by order_id presence (null = pending, not null = received)
        
        $zydaOrder->save();
        
        Log::info('💾 Zyda order saved', [
            'zyda_order_id' => $id,
            'location' => $zydaOrder->location,
            'latitude' => $zydaOrder->latitude,
            'longitude' => $zydaOrder->longitude,
        ]);

        // Check if order already exists for this zyda_order
        if ($zydaOrder->order_id) {
            $existingOrder = Order::find($zydaOrder->order_id);
            if ($existingOrder) {
                // Update existing order location if needed
                if ($coordinates) {
                    $existingOrder->customer_latitude = $coordinates['latitude'];
                    $existingOrder->customer_longitude = $coordinates['longitude'];
                    $existingOrder->save();
                    
                    Log::info('✅ Existing order location updated', [
                        'order_id' => $existingOrder->id,
                        'latitude' => $coordinates['latitude'],
                        'longitude' => $coordinates['longitude'],
                    ]);
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
            Log::info('🔗 Processing URL to extract coordinates', [
                'url' => $trimmedLocation,
            ]);
            
            $urlData = $this->extractCoordinatesAndUrl($trimmedLocation);
            if ($urlData && is_array($urlData)) {
                $result['coordinates'] = $urlData['coordinates'] ?? null;
                $result['final_url'] = $urlData['final_url'] ?? $trimmedLocation;
                
                Log::info('✅ URL data extracted', [
                    'original_url' => $trimmedLocation,
                    'final_url' => $result['final_url'],
                    'has_coordinates' => !empty($result['coordinates']),
                ]);
                
                // Return result even if coordinates are null, so we can save the final URL
                return $result;
            } else {
                // If extraction failed completely, return result with original URL
                Log::warning('⚠️ extractCoordinatesAndUrl returned null or invalid', [
                    'url' => $trimmedLocation,
                    'url_data' => $urlData,
                ]);
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
        
        Log::info('🔗 Starting URL processing to extract original link and coordinates', [
            'original_url' => $url,
        ]);
        
        // First, get the final URL after following redirects using curl
        // This will resolve ANY short link to its original Google Maps link
        $finalUrl = $this->extractCoordinatesFromUrlInternal($url);
        
        // Always use the final URL (original link) even if it's the same as input
        // This ensures we save the resolved URL in the database
        if ($finalUrl) {
            $result['final_url'] = $finalUrl;
            Log::info('✅ Original link retrieved', [
                'original_url' => $url,
                'final_url' => $finalUrl,
                'url_changed' => $finalUrl !== $url,
            ]);
        } else {
            // Fallback to original URL if extraction failed
            $result['final_url'] = $url;
            Log::warning('⚠️ Could not retrieve original link, using input URL', [
                'original_url' => $url,
            ]);
        }
        
        // Now extract coordinates from final URL (original Google Maps link)
        $coordinates = $this->extractCoordinatesFromFinalUrl($result['final_url']);
        $result['coordinates'] = $coordinates;
        
        // Log the extraction result
        if ($coordinates) {
            Log::info('✅ Coordinates extracted from original link', [
                'original_url' => $url,
                'final_url' => $result['final_url'],
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
            ]);
        } else {
            Log::warning('⚠️ Could not extract coordinates from original link', [
                'original_url' => $url,
                'final_url' => $result['final_url'],
            ]);
        }
        
        // Always return result even if coordinates are null, so we can save the original link
        // The original link (final_url) will be saved in the location column
        return $result;
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
     * 
     * ALWAYS follows redirects for ANY URL to get the original Google Maps link
     * This ensures that short links (like zyda.co, bit.ly, etc.) are resolved
     * to their final destination before extracting coordinates
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
        
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        // Check if it's already a Google Maps URL
        $isGoogleMaps = (strpos($host, 'google.com') !== false && strpos($host, 'maps') !== false) || 
                       (strpos($host, 'maps.google.com') !== false) ||
                       (strpos($url, 'google.com/maps') !== false);
        
        // ALWAYS follow redirects to get the original link from any short link
        // Even if it looks like a Google Maps URL, we still follow redirects
        // to ensure we get the final, canonical URL
        try {
            Log::info('🔗 Following URL redirects to get original Google Maps link', [
                'original_url' => $url,
                'host' => $host,
                'is_google_maps' => $isGoogleMaps,
            ]);
            
            // Use cURL to get final URL after following redirects
            // This will resolve ANY short link to its final destination
            $finalUrl = $this->getFinalUrlFromRedirects($url);
            
            if ($finalUrl && $finalUrl !== $url) {
                Log::info('✅ URL redirect resolved successfully', [
                    'original_url' => $url,
                    'final_url' => $finalUrl,
                ]);
                return $finalUrl;
            } else {
                // If no redirect found or same URL, check if it's already a valid Google Maps URL
                if ($isGoogleMaps) {
                    Log::info('✅ URL is already a Google Maps URL', [
                        'url' => $url,
                    ]);
                    return $url;
                } else {
                    Log::warning('⚠️ Could not resolve URL redirect and URL is not Google Maps', [
                        'original_url' => $url,
                        'final_url' => $finalUrl,
                    ]);
                    // Return original URL even if redirect failed
                    return $url;
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Error following URL redirect', [
                'original_url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Continue with original URL if redirect fails
            return $url;
        }
    }
    
    /**
     * Extract coordinates from final Google Maps URL
     * Supports various Google Maps URL formats:
     * - https://www.google.com/maps?q=24.7136,46.6753
     * - https://www.google.com/maps/@24.7136,46.6753,15z
     * - https://www.google.com/maps/place/.../@24.7136,46.6753,15z
     * - https://maps.google.com/?q=24.7136,46.6753
     * - https://www.google.com/maps/dir/.../@24.7136,46.6753
     * - https://maps.app.goo.gl/xxxxx (new Google Maps short links)
     */
    protected function extractCoordinatesFromFinalUrl(string $url): ?array
    {
        // Parse final URL
        $parsedUrl = parse_url($url);
        
        // Extract from query parameters (q=lat,lng or ll=lat,lng)
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            
            // Try 'q' parameter first
            if (isset($queryParams['q'])) {
                $qValue = $queryParams['q'];
                if (preg_match('/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $qValue, $matches)) {
                    $lat = (float) $matches[1];
                    $lng = (float) $matches[2];
                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                        Log::info('✅ Coordinates extracted from query parameter q', [
                            'url' => $url,
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ]);
                        return [
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ];
                    }
                }
            }
            
            // Try 'll' parameter (lat,lng)
            if (isset($queryParams['ll'])) {
                $llValue = $queryParams['ll'];
                if (preg_match('/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $llValue, $matches)) {
                    $lat = (float) $matches[1];
                    $lng = (float) $matches[2];
                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                        Log::info('✅ Coordinates extracted from query parameter ll', [
                            'url' => $url,
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ]);
                        return [
                            'latitude' => $lat,
                            'longitude' => $lng,
                        ];
                    }
                }
            }
            
            // Try separate 'lat' and 'lng' parameters
            if (isset($queryParams['lat']) && isset($queryParams['lng'])) {
                $lat = (float) $queryParams['lat'];
                $lng = (float) $queryParams['lng'];
                if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                    Log::info('✅ Coordinates extracted from separate lat/lng parameters', [
                        'url' => $url,
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ]);
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ];
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
                    Log::info('✅ Coordinates extracted from path @ format', [
                        'url' => $url,
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ]);
                    return [
                        'latitude' => $lat,
                        'longitude' => $lng,
                    ];
                }
            }
        }

        // Try to find coordinates anywhere in the final URL (last resort)
        if (preg_match('/([-+]?\d{1,2}\.?\d*),([-+]?\d{1,3}\.?\d*)/', $url, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[2];
            // Validate coordinates range (latitude: -90 to 90, longitude: -180 to 180)
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                Log::info('✅ Coordinates extracted from URL pattern match', [
                    'url' => $url,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ]);
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }
        }

        Log::warning('⚠️ Could not extract coordinates from URL', [
            'url' => $url,
            'parsed_url' => $parsedUrl,
        ]);
        
        return null;
    }

    /**
     * Get final URL after following redirects using cURL
     * Supports short links like zyda.co, is.gd, bit.ly, etc.
     * This method opens the URL and follows ALL redirects until it reaches the final destination
     */
    protected function getFinalUrlFromRedirects(string $url): string
    {
        try {
            Log::info('🔗 Opening URL and following all redirects', [
                'original_url' => $url,
            ]);
            
            // Initialize cURL
            $ch = curl_init($url);
            
            // Set cURL options for following redirects
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,      // Return response as string
                CURLOPT_FOLLOWLOCATION => true,      // Follow redirects
                CURLOPT_MAXREDIRS => 20,             // Maximum number of redirects (increased for complex redirect chains)
                CURLOPT_TIMEOUT => 30,               // Timeout in seconds (increased for slow redirects)
                CURLOPT_CONNECTTIMEOUT => 10,        // Connection timeout
                CURLOPT_SSL_VERIFYPEER => false,     // Disable SSL verification (for some redirects)
                CURLOPT_SSL_VERIFYHOST => false,     // Disable host verification
                CURLOPT_HEADER => true,              // Include headers in output
                CURLOPT_NOBODY => true,              // HEAD request only (faster, doesn't download body)
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36', // Browser user agent
                CURLOPT_AUTOREFERER => true,         // Automatically set Referer header
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Use HTTP/1.1
            ]);
            
            // Execute request
            $response = curl_exec($ch);
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $errorCode = curl_errno($ch);
                curl_close($ch);
                
                Log::warning('⚠️ cURL error following redirects', [
                    'url' => $url,
                    'error_code' => $errorCode,
                    'error' => $error,
                ]);
                
                // If HEAD request failed, try GET request as fallback
                return $this->getFinalUrlFromRedirectsGet($url);
            }
            
            // Get final URL after all redirects
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
            curl_close($ch);
            
            Log::info('📊 Redirect following results', [
                'original_url' => $url,
                'final_url' => $finalUrl,
                'http_code' => $httpCode,
                'redirect_count' => $redirectCount,
                'is_google_maps' => strpos($finalUrl, 'google.com/maps') !== false || strpos($finalUrl, 'maps.google.com') !== false,
            ]);
            
            // Validate final URL
            if ($finalUrl && $finalUrl !== $url) {
                if ($httpCode < 400) {
                    Log::info('✅ Successfully followed redirects to final URL', [
                        'original_url' => $url,
                        'final_url' => $finalUrl,
                        'http_code' => $httpCode,
                        'redirect_count' => $redirectCount,
                    ]);
                    return $finalUrl;
                } else {
                    Log::warning('⚠️ Redirect followed but HTTP code indicates error', [
                        'original_url' => $url,
                        'final_url' => $finalUrl,
                        'http_code' => $httpCode,
                    ]);
                    // Still return final URL even if HTTP code is not 2xx
                    return $finalUrl;
                }
            } else {
                Log::warning('⚠️ No redirects found or final URL same as original', [
                    'original_url' => $url,
                    'final_url' => $finalUrl,
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
    
    /**
     * Fallback method: Get final URL using GET request instead of HEAD
     * Some servers don't handle HEAD requests properly
     */
    protected function getFinalUrlFromRedirectsGet(string $url): string
    {
        try {
            Log::info('🔄 Trying GET request as fallback', [
                'url' => $url,
            ]);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 20,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,             // GET request (download body)
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                CURLOPT_AUTOREFERER => true,
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                Log::error('❌ GET request also failed', [
                    'url' => $url,
                    'error' => $error,
                ]);
                return $url;
            }
            
            $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($finalUrl && $finalUrl !== $url) {
                Log::info('✅ GET request successfully followed redirects', [
                    'original_url' => $url,
                    'final_url' => $finalUrl,
                    'http_code' => $httpCode,
                ]);
                return $finalUrl;
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception in GET fallback', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $url;
    }
}

