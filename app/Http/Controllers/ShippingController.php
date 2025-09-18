<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShippingService;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    protected $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    public function handleWebhook(Request $request)
    {
        $this->shippingService->handleWebhook($request);
        return response()->json(['message' => 'Webhook processed']);
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

        $shippingOrder = $this->shippingService->createOrder($order);
        if (!$shippingOrder) {
            return response()->json(['error' => 'Failed to create shipping order. Check logs.'], 500);
        }

        return response()->json($shippingOrder);
    }

    public function getStatus(string $dspOrderId)
    {
        $api = $this->shippingService->getOrderStatus($dspOrderId);
        $local = DB::table('shipping_orders')->where('dsp_order_id', $dspOrderId)->first();
        return response()->json(['api' => $api, 'local' => $local]);
    }

    public function cancel(string $dspOrderId)
    {
        $result = $this->shippingService->cancelOrder($dspOrderId);

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
