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
use Illuminate\Support\Str;
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
        // CRITICAL: Log entry point to verify function is called
        Log::info('🚨 UPDATE LOCATION FUNCTION CALLED', [
            'zyda_order_id' => $id,
            'request_location' => $request->input('location'),
            'request_all' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        $validated = $request->validate([
            // نسمح بروابط أطول (حتى 2048) لأن روابط جوجل ماب قد تكون طويلة
            'location' => 'nullable|string|max:2048',
        ]);

        Log::info('✅ Validation passed', [
            'zyda_order_id' => $id,
            'validated_location' => $validated['location'] ?? 'NULL',
        ]);

        $zydaOrder = ZydaOrder::find($id);

        if (!$zydaOrder) {
            Log::error('❌ Zyda order not found', [
                'zyda_order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Zyda order not found',
            ], 404);
        }

        Log::info('✅ Zyda order found', [
            'zyda_order_id' => $zydaOrder->id,
            'existing_order_id' => $zydaOrder->order_id ?? 'NULL',
            'existing_location' => $zydaOrder->location ?? 'NULL',
        ]);

        // Use curl to resolve short link to original URL
        // Save the original URL (not the short link) in database
        $incomingLocation = isset($validated['location']) ? trim($validated['location']) : null;
        // قصّ أي رابط طويل ليتماشى مع حد التخزين الآمن (نفس حد الفاليديشن)
        if (!empty($incomingLocation) && Str::length($incomingLocation) > 2048) {
            $incomingLocation = Str::limit($incomingLocation, 2048, '');
        }

        Log::info('📥 Processing location update - resolving short link using curl', [
            'zyda_order_id' => $id,
            'location' => $incomingLocation,
        ]);
        
        // Initialize coordinates variable
        $coordinates = null;
        $originalUrl = $incomingLocation;

        // Check if it's a URL (short link or regular)
        if (!empty($incomingLocation) && filter_var($incomingLocation, FILTER_VALIDATE_URL)) {
            // Use curl to resolve short link to original URL
            Log::info('🔗 Resolving short link to original URL using curl', [
                'short_link' => $incomingLocation,
            ]);
            
            $resolvedUrl = $this->getFinalUrlFromRedirects($incomingLocation);
            
            if ($resolvedUrl && $resolvedUrl !== $incomingLocation) {
                // Successfully resolved short link
                $originalUrl = $resolvedUrl;
                Log::info('✅ Short link resolved to original URL', [
                    'short_link' => $incomingLocation,
                    'original_url' => $originalUrl,
                ]);
            } else {
                // No redirect or same URL - use original
                Log::info('ℹ️ No redirect found or URL is already original', [
                    'url' => $incomingLocation,
                    'resolved_url' => $resolvedUrl,
                ]);
                $originalUrl = $incomingLocation;
            }
            
            // Try to extract coordinates from original URL
            $locationData = $this->parseLocationAndExtractUrl($originalUrl);
            if ($locationData && isset($locationData['coordinates']) && $locationData['coordinates']) {
                $coordinates = $locationData['coordinates'];
                Log::info('✅ Coordinates extracted from original URL', [
                    'original_url' => $originalUrl,
                    'latitude' => $coordinates['latitude'],
                    'longitude' => $coordinates['longitude'],
                ]);
            }
        } else {
            // Not a URL - try to parse as coordinates or other format
            $locationData = $this->parseLocationAndExtractUrl($validated['location']);
            if ($locationData && isset($locationData['final_url'])) {
                $originalUrl = $locationData['final_url'];
            }
            if ($locationData && isset($locationData['coordinates']) && $locationData['coordinates']) {
                $coordinates = $locationData['coordinates'];
            }
        }
        
        // Save original URL (resolved from short link) in database
        $zydaOrder->location = $originalUrl;
        
        // Save coordinates if extracted
        if ($coordinates) {
            $zydaOrder->latitude = $coordinates['latitude'];
            $zydaOrder->longitude = $coordinates['longitude'];
            
            Log::info('✅ Original URL and coordinates saved', [
                'zyda_order_id' => $id,
                'original_url' => $originalUrl,
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
            ]);
        } else {
            Log::info('✅ Original URL saved (no coordinates extracted)', [
                'zyda_order_id' => $id,
                'original_url' => $originalUrl,
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
        Log::info('🔍 Checking if location provided to create Order', [
            'zyda_order_id' => $zydaOrder->id,
            'validated_location' => $validated['location'] ?? 'NULL',
            'validated_location_empty' => empty($validated['location'] ?? null),
            'zyda_order_location' => $zydaOrder->location ?? 'NULL',
            'has_coordinates' => !empty($zydaOrder->latitude) && !empty($zydaOrder->longitude),
        ]);

        if (!empty($validated['location'])) {
            Log::info('✅ Location provided - Will create Order from ZydaOrder', [
                'zyda_order_id' => $zydaOrder->id,
                'location' => $validated['location'],
            ]);

            try {
                Log::info('🚀 Starting Order creation from ZydaOrder', [
                    'zyda_order_id' => $zydaOrder->id,
                    'location' => $zydaOrder->location,
                    'has_coordinates' => !empty($zydaOrder->latitude) && !empty($zydaOrder->longitude),
                    'step' => 'Calling createOrderFromZydaOrder()',
                ]);

                // Create Order - boot method will automatically send to shipping company
                $createdOrder = $this->createOrderFromZydaOrder($zydaOrder);

                // Refresh order to get latest data including dsp_order_id if it was set by boot method
                $createdOrder->refresh();

                Log::info('✅ Order created from ZydaOrder - Checking if sent to shipping', [
                    'order_id' => $createdOrder->id,
                    'order_number' => $createdOrder->order_number,
                    'dsp_order_id' => $createdOrder->dsp_order_id ?? 'NULL',
                    'shop_id' => $createdOrder->shop_id,
                    'shipping_status' => $createdOrder->shipping_status,
                    'note' => 'Order Model boot method should have sent to shipping company automatically',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated and order created successfully',
                    'data' => $zydaOrder,
                    'order_id' => $createdOrder->id,
                    'order_number' => $createdOrder->order_number,
                    'dsp_order_id' => $createdOrder->dsp_order_id,
                    'shipping_status' => $createdOrder->shipping_status,
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Exception while creating order from ZydaOrder', [
                    'zyda_order_id' => $zydaOrder->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Location updated successfully, but failed to create order: ' . $e->getMessage(),
                    'data' => $zydaOrder,
                ], 200);
            }
        } else {
            Log::warning('⚠️ Location not provided - Order will NOT be created', [
                'zyda_order_id' => $zydaOrder->id,
                'validated_location' => $validated['location'] ?? 'NULL',
                'note' => 'Order creation skipped because location is empty',
            ]);
        }

        Log::info('✅ updateLocation function completed', [
            'zyda_order_id' => $zydaOrder->id,
            'order_id' => $zydaOrder->order_id ?? 'NULL',
        ]);

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
     * IMPORTANT: Uses same configuration as successful orders to ensure shipping company accepts it
     */
    protected function createOrderFromZydaOrder(ZydaOrder $zydaOrder): Order
    {
        // IMPORTANT: Use first available user and restaurant to ensure compatibility
        // This ensures Zyda orders are accepted by shipping company
        $user = User::first();
        if (!$user) {
            throw new \Exception("No users found in database. At least one user is required for Zyda orders.");
        }
        $userId = $user->id;

        // IMPORTANT: All Zyda orders must use Gather Us restaurant (shop_id = 210)
        // This ensures all Zyda orders are linked to the correct restaurant
        $restaurant = Restaurant::where('name', 'Gather Us')->first();
        if (!$restaurant) {
            // Fallback: Try to find by shop_id = 210
            $restaurant = Restaurant::where('shop_id', '210')->first();
        }
        if (!$restaurant) {
            // Last fallback: Use first available restaurant
            $restaurant = Restaurant::whereNotNull('shop_id')->first() ?? Restaurant::first();
        }
        if (!$restaurant) {
            throw new \Exception("No restaurants found in database. At least one restaurant is required for Zyda orders.");
        }
        $restaurantId = $restaurant->id;
        
        Log::info('✅ Using Gather Us restaurant for Zyda order', [
            'user_id' => $userId,
            'user_name' => $user->name,
            'restaurant_id' => $restaurantId,
            'restaurant_name' => $restaurant->name,
            'restaurant_shop_id' => $restaurant->shop_id ?? 'NULL',
            'note' => 'All Zyda orders are linked to Gather Us restaurant (shop_id = 210)',
        ]);

        // Parse location to get coordinates
        $locationData = $this->parseLocationAndExtractUrl($zydaOrder->location);
        $coordinates = $locationData['coordinates'] ?? null;
        
        // IMPORTANT: All Zyda orders must use shop_id = 210 (Gather Us)
        // This ensures all Zyda orders are sent to the correct branch in shipping company
        $shopId = $restaurant->shop_id ?? '210';
        
        // Force shop_id = 210 for Gather Us (even if restaurant has different shop_id)
        if ($restaurant->name === 'Gather Us' || $restaurant->shop_id === '210') {
            $shopId = '210';
            Log::info('✅ Using shop_id = 210 for Gather Us (Zyda orders)', [
                'restaurant_id' => $restaurantId,
                'restaurant_name' => $restaurant->name,
                'shop_id' => $shopId,
            ]);
        } else {
            // Fallback: If restaurant doesn't have shop_id, use 210 (Gather Us)
            if (empty($shopId) || $shopId !== '210') {
                $shopId = '210';
                Log::warning('⚠️ Restaurant shop_id not 210, forcing shop_id = 210 for Zyda orders', [
                    'restaurant_id' => $restaurantId,
                    'restaurant_name' => $restaurant->name,
                    'restaurant_shop_id' => $restaurant->shop_id ?? 'NULL',
                    'forced_shop_id' => $shopId,
                ]);
            }
        }

        Log::info('🔍 Zyda Order Creation - Using restaurant shop_id for shipping compatibility', [
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'shop_id' => $shopId,
            'shop_id_source' => $restaurant->shop_id ? 'restaurant.shop_id' : 'default_fallback',
            'default_shipping_provider' => \App\Models\AppSetting::get('default_shipping_provider', 'leajlak'),
            'note' => 'Using shop_id from restaurant to ensure compatibility with active shipping provider',
        ]);

        // Generate unique order number (similar to rest-links but with ZYDA prefix)
        // Example: ZYDA-20251120-99F962
        $orderNumber = 'ZYDA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Use the exact total_amount from Zyda order (no additional fees or tax)
        // Each order should have its own data only, as it comes from Zyda
        $totalAmount = (float) $zydaOrder->total_amount;

        // Validate required data for shipping
        $deliveryName = $zydaOrder->name ?? 'عميل';
        $deliveryPhone = $zydaOrder->phone ?? null;
        $deliveryAddress = $zydaOrder->address ?? 'عنوان غير محدد';
        $customerLatitude = $coordinates['latitude'] ?? null;
        $customerLongitude = $coordinates['longitude'] ?? null;

        // Log validation before creating order
        Log::info('📦 Creating Order from ZydaOrder - Validating required data for shipping', [
            'zyda_order_id' => $zydaOrder->id,
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'restaurant_id' => $restaurantId,
            'shop_id' => $shopId,
            'total_amount' => $totalAmount,
            'delivery_name' => $deliveryName,
            'delivery_phone' => $deliveryPhone,
            'delivery_address' => $deliveryAddress,
            'has_coordinates' => !empty($customerLatitude) && !empty($customerLongitude),
            'customer_latitude' => $customerLatitude,
            'customer_longitude' => $customerLongitude,
            'status' => 'confirmed',
            'payment_method' => 'online',
            'payment_status' => 'paid',
        ]);

        // Validate critical fields before creating order
        $missingFields = [];
        if (empty($deliveryName)) {
            $missingFields[] = 'delivery_name';
        }
        if (empty($deliveryPhone)) {
            $missingFields[] = 'delivery_phone';
        }
        if (empty($deliveryAddress)) {
            $missingFields[] = 'delivery_address';
        }
        if (empty($customerLatitude) || empty($customerLongitude)) {
            $missingFields[] = 'coordinates';
        }

        if (!empty($missingFields)) {
            Log::warning('⚠️ Missing required fields for shipping', [
                'zyda_order_id' => $zydaOrder->id,
                'missing_fields' => $missingFields,
            ]);
        }

        // IMPORTANT: Create Order - نفس آلية rest-links:
        // 1) إنشاء Order في قاعدة البيانات
        // 2) Order Model boot (static::created) يستدعي ShippingService::createOrder
        // 3) dsp_order_id يتم حفظه في الـ Order عند نجاح شركة الشحن
        Log::info('📝 Creating Order from ZydaOrder using Order model boot (same as rest-links)', [
            'zyda_order_id' => $zydaOrder->id,
            'order_number' => $orderNumber,
            'shop_id' => $shopId,
            'has_coordinates' => !empty($customerLatitude) && !empty($customerLongitude),
            'delivery_name' => $deliveryName,
            'delivery_phone' => $deliveryPhone,
            'delivery_address' => $deliveryAddress,
            'step' => 'Calling Order::create() - Order::boot will handle shipping',
        ]);

        // Get default shipping provider from settings
        $defaultShippingProvider = \App\Models\AppSetting::get('default_shipping_provider', 'leajlak');
        
        // Create Order WITHOUT calling ShippingService manually
        // Order::boot (static::created) سيتكفل بإرسال الطلب لشركة الشحن وتوليد dsp_order_id
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $userId, // ثابت: 36
            'restaurant_id' => $restaurantId, // ثابت: 821017372
            'shop_id' => $shopId, // من Restaurant أو default حسب shipping provider
            'status' => 'confirmed',
            'shipping_status' => 'New Order',
            'shipping_provider' => $defaultShippingProvider, // استخدام shipping provider الحالي من الإعدادات
            'source' => 'zyda',
            'subtotal' => $totalAmount,
            'delivery_fee' => 0,
            'tax' => 0,
            'total' => $totalAmount,
            'delivery_name' => $deliveryName,
            'delivery_phone' => $deliveryPhone,
            'delivery_address' => $deliveryAddress,
            'customer_latitude' => $customerLatitude,
            'customer_longitude' => $customerLongitude,
            'payment_method' => 'online',
            'payment_status' => 'paid', // طلب Zyda مدفوع
            'sound' => true,
            // مفيش dsp_order_id هنا – هيتم توليده فقط من شركة الشحن داخل Order::boot
        ]);

        Log::info('✅ Order created from ZydaOrder - waiting for Order::boot to contact shipping', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'shop_id' => $order->shop_id,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'source' => $order->source,
            'dsp_order_id' => $order->dsp_order_id ?? 'NULL (Order::boot will try to set this from shipping)',
            'shipping_status' => $order->shipping_status ?? 'New Order',
            'has_coordinates' => !empty($order->customer_latitude) && !empty($order->customer_longitude),
            'customer_latitude' => $order->customer_latitude,
            'customer_longitude' => $order->customer_longitude,
            'delivery_name' => $order->delivery_name,
            'delivery_phone' => $order->delivery_phone,
            'delivery_address' => $order->delivery_address,
            'total' => $order->total,
            'note' => 'Following same pattern as RestLinkController: create with pending → update to paid → static::updated sends to shipping',
            'next_step' => 'Check logs for: 🔄 ORDER MODEL UPDATED EVENT TRIGGERED → ✅ PAYMENT_STATUS CHANGED TO PAID',
        ]);

        // Link zyda_order to the created order
        $zydaOrder->order_id = $order->id;
        $zydaOrder->save();

        // Create order items from zyda_order items
        if (!empty($zydaOrder->items)) {
            // Parse items (can be JSON string or array)
            $items = is_string($zydaOrder->items) ? json_decode($zydaOrder->items, true) : $zydaOrder->items;
            
            if (is_array($items) && count($items) > 0) {
                // Use restaurant_id from order (821017372) to find menu items
                $menuItem = MenuItem::where('restaurant_id', $restaurantId)->first();
                
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
     * Check if URL is a Google Maps URL
     * Supports various Google Maps URL formats
     */
    protected function isGoogleMapsUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }
        
        // Normalize URL (lowercase for comparison)
        $urlLower = strtolower($url);
        
        // Parse URL to get components
        $parsedUrl = parse_url($url);
        $host = strtolower($parsedUrl['host'] ?? '');
        $path = strtolower($parsedUrl['path'] ?? '');
        $query = strtolower($parsedUrl['query'] ?? '');
        
        Log::info('🔍 Checking if URL is Google Maps', [
            'url' => $url,
            'host' => $host,
            'path' => $path,
        ]);
        
        // Check for Google Maps domains (more flexible)
        $googleMapsDomains = [
            'maps.google.com',
            'www.google.com',
            'google.com',
            'maps.app.goo.gl',
            'goo.gl',
        ];
        
        foreach ($googleMapsDomains as $domain) {
            if ($host === $domain || strpos($host, $domain) !== false) {
                // For google.com, check if it's maps related
                if ($domain === 'google.com' || $domain === 'www.google.com') {
                    // Check if path or query contains 'maps'
                    if (strpos($path, 'maps') !== false || 
                        strpos($urlLower, '/maps') !== false ||
                        strpos($query, 'maps') !== false) {
                        Log::info('✅ URL is Google Maps (google.com with maps)', [
                            'url' => $url,
                            'domain' => $domain,
                        ]);
                        return true;
                    }
                } elseif ($domain === 'maps.google.com') {
                    // maps.google.com is always Google Maps
                    Log::info('✅ URL is Google Maps (maps.google.com)', [
                        'url' => $url,
                    ]);
                    return true;
                } elseif ($domain === 'maps.app.goo.gl' || $domain === 'goo.gl') {
                    // Google short links - assume Maps if domain matches
                    Log::info('✅ URL is Google Maps (Google short link)', [
                        'url' => $url,
                        'domain' => $domain,
                    ]);
                    return true;
                }
            }
        }
        
        // Also check if URL string contains Google Maps patterns
        $mapsPatterns = [
            'google.com/maps',
            'maps.google.com',
            'maps/app/goo.gl',
            'goo.gl/maps',
        ];
        
        foreach ($mapsPatterns as $pattern) {
            if (strpos($urlLower, $pattern) !== false) {
                Log::info('✅ URL is Google Maps (pattern match)', [
                    'url' => $url,
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }
        
        // If URL contains coordinates and looks like it came from curl following redirects,
        // it's likely a valid location link (probably Google Maps)
        // This is a fallback for edge cases where the domain might not be recognized
        if (preg_match('/([-+]?\d{1,2}\.?\d*),([-+]?\d{1,3}\.?\d*)/', $urlLower)) {
            // If URL contains coordinates pattern and is HTTPS, accept it
            // (Most location services use HTTPS and coordinates)
            if (strpos($urlLower, 'https://') === 0 || strpos($urlLower, 'http://') === 0) {
                Log::info('✅ URL accepted: Contains coordinates and is valid URL', [
                    'url' => $url,
                ]);
                return true;
            }
        }
        
        Log::warning('⚠️ URL is NOT identified as Google Maps', [
            'url' => $url,
            'host' => $host,
            'path' => $path,
        ]);
        
        return false;
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
        // This will resolve ANY short link to its original URL
        $finalUrl = $this->extractCoordinatesFromUrlInternal($url);
        
        // Always use the final URL (resolved from short link or original)
        if ($finalUrl) {
            $result['final_url'] = $finalUrl;
            Log::info('✅ Original link retrieved using curl', [
                'original_url' => $url,
                'final_url' => $finalUrl,
                'url_changed' => $finalUrl !== $url,
            ]);
        } else {
            // Fallback to original URL if curl failed
            $result['final_url'] = $url;
            Log::warning('⚠️ Curl failed to resolve URL, using original', [
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
     * Internal method to get final URL after following redirects using curl
     * Returns the final URL after following all redirects (any type, not just Google Maps)
     * 
     * ALWAYS follows redirects for ANY URL to get the original link
     * This ensures that short links (like zyda.co, bit.ly, etc.) are resolved
     * to their final destination
     */
    protected function extractCoordinatesFromUrlInternal(string $url): ?string
    {
        // Google Maps formats:
        // https://www.google.com/maps?q=24.7136,46.6753
        // https://www.google.com/maps/@24.7136,46.6753,15z
        // https://www.google.com/maps/place/.../@24.7136,46.6753,15z
        // https://maps.google.com/?q=24.7136,46.6753
        // https://www.google.com/maps/dir/.../@24.7136,46.6753
        // Short links: https://is.gd/ZpIRU1, https://bit.ly/xxx, https://zyda.co/o2CONfN, etc.
        
        // ALWAYS follow redirects to get the original link from any short link
        // This will resolve ANY short link to its final destination
        try {
            Log::info('🔗 Following URL redirects using curl to get original link', [
                'original_url' => $url,
            ]);
            
            // Use cURL to get final URL after following redirects
            // This will resolve ANY short link to its final destination
            $finalUrl = $this->getFinalUrlFromRedirects($url);
            
            if ($finalUrl && $finalUrl !== $url) {
                // Successfully resolved redirect
                Log::info('✅ URL redirect resolved to original link', [
                    'original_url' => $url,
                    'final_url' => $finalUrl,
                ]);
                return $finalUrl;
            } else {
                // No redirect found or same URL - return original URL
                Log::info('ℹ️ No redirect found or URL is already original', [
                    'url' => $url,
                    'final_url' => $finalUrl,
                ]);
                return $url; // Return original URL
            }
        } catch (\Exception $e) {
            Log::error('❌ Error following URL redirect', [
                'original_url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Return original URL if redirect fails
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
     * - https://www.google.com/maps/dir/24.7562097,46.6746282/24.7542315,46.6743851/... (direction link - extracts destination coordinates)
     * - https://maps.app.goo.gl/xxxxx (new Google Maps short links)
     * 
     * For dir (direction) links, extracts DESTINATION coordinates (second pair)
     */
    protected function extractCoordinatesFromFinalUrl(string $url): ?array
    {
        // Parse final URL
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // PRIORITY 1: Check for dir (direction) links
        // Format: /dir/lat1,lng1/lat2,lng2/...
        // We want the DESTINATION coordinates (lat2,lng2 - the second pair)
        if (preg_match('/\/dir\/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)\/([-+]?\d+\.?\d*),([-+]?\d+\.?\d*)/', $path, $matches)) {
            // matches[1] and matches[2] are origin (start point)
            // matches[3] and matches[4] are destination (end point) - we want these
            $lat = (float) $matches[3];
            $lng = (float) $matches[4];
            
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                Log::info('✅ Destination coordinates extracted from dir (direction) link', [
                    'url' => $url,
                    'origin_lat' => (float) $matches[1],
                    'origin_lng' => (float) $matches[2],
                    'destination_lat' => $lat,
                    'destination_lng' => $lng,
                ]);
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            }
        }
        
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

