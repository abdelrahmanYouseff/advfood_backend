<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShippingServiceFactory;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    /**
     * Handle webhook from Leajlak shipping provider
     */
    public function handleWebhook(Request $request)
    {
        $service = ShippingServiceFactory::getService('leajlak');
        $service->handleWebhook($request);
        return response()->json(['message' => 'Webhook processed']);
    }

    /**
     * Handle webhook from Shadda shipping provider
     */
    public function handleShaddaWebhook(Request $request)
    {
        $service = ShippingServiceFactory::getService('shadda');
        $service->handleWebhook($request);
        return response()->json(['message' => 'Shadda webhook processed']);
    }

    public function createOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        $orderNumber = $request->input('order_number');

        $order = null;
        if ($orderId) {
            $order = Order::find($orderId);
        } elseif ($orderNumber) {
            $order = Order::where('order_number', $orderNumber)->first();
        }

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Get shipping provider from order or use default
        $provider = $order->shipping_provider ?? \App\Models\AppSetting::get('default_shipping_provider', 'leajlak');

        // Log attempt
        \Illuminate\Support\Facades\Log::info('🔄 Manual shipping order creation attempt', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'shop_id' => $order->shop_id,
            'shipping_provider' => $provider,
            'current_dsp_order_id' => $order->dsp_order_id,
        ]);

        // Get the appropriate shipping service
        $shippingService = ShippingServiceFactory::getService($provider);
        $shippingOrder = $shippingService->createOrder($order);
        if (!$shippingOrder) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create shipping order. Check logs for details.',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => 'Review storage/logs/laravel.log for detailed error information'
            ], 500);
        }

        // Update order with dsp_order_id if returned
        if (isset($shippingOrder['dsp_order_id'])) {
            $order->dsp_order_id = $shippingOrder['dsp_order_id'];
            $order->shipping_status = $shippingOrder['shipping_status'] ?? 'New Order';
            $order->save();

            \Illuminate\Support\Facades\Log::info('✅ Order updated with shipping information', [
                'order_id' => $order->id,
                'dsp_order_id' => $order->dsp_order_id,
                'shipping_status' => $order->shipping_status,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order sent to shipping company successfully',
            'data' => $shippingOrder,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'dsp_order_id' => $order->dsp_order_id,
                'shipping_status' => $order->shipping_status,
            ]
        ]);
    }

    public function getStatus(string $dspOrderId)
    {
        // Try to find order to determine provider
        $order = Order::where('dsp_order_id', $dspOrderId)->first();
        $provider = $order ? ($order->shipping_provider ?? 'leajlak') : 'leajlak';
        
        $shippingService = ShippingServiceFactory::getService($provider);
        $api = $shippingService->getOrderStatus($dspOrderId);
        $local = DB::table('shipping_orders')->where('dsp_order_id', $dspOrderId)->first();
        return response()->json(['api' => $api, 'local' => $local, 'provider' => $provider]);
    }

    public function cancel(string $dspOrderId)
    {
        // Try to find order to determine provider
        $order = Order::where('dsp_order_id', $dspOrderId)->first();
        $provider = $order ? ($order->shipping_provider ?? 'leajlak') : 'leajlak';
        
        $shippingService = ShippingServiceFactory::getService($provider);
        $result = $shippingService->cancelOrder($dspOrderId);

        if (is_array($result)) {
            // Provider returned error details
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Cancellation failed',
                'provider_response' => $result
            ], $result['status_code'] ?? 400);
        }

        return response()->json(['success' => (bool) $result]);
    }
}
