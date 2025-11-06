<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Noon payment webhooks.
     */
    public function handleNoon(Request $request)
    {
        $payload = $request->all();

        Log::info('🔔 Noon webhook received', [
            'payload' => $payload,
            'headers' => $request->headers->all(),
        ]);

        $orderReference = $this->extractOrderReference($payload);

        if (!$orderReference) {
            Log::warning('⚠️ Noon webhook missing order reference', [
                'payload' => $payload,
            ]);

            return response()->json([
                'status' => 'ignored',
                'reason' => 'order_reference_missing',
            ], 202);
        }

        $order = Order::query()
            ->where('payment_order_reference', $orderReference)
            ->orWhere('order_number', $orderReference)
            ->orWhere('id', $orderReference)
            ->latest()
            ->first();

        if (!$order) {
            Log::warning('⚠️ Noon webhook order not found', [
                'order_reference' => $orderReference,
            ]);

            return response()->json([
                'status' => 'ignored',
                'reason' => 'order_not_found',
            ], 202);
        }

        if (!$this->isPaymentSuccessful($payload)) {
            Log::info('ℹ️ Noon webhook indicates payment not successful yet', [
                'order_id' => $order->id,
                'order_reference' => $orderReference,
            ]);

            return response()->json([
                'status' => 'pending',
                'reason' => 'payment_not_successful',
            ], 202);
        }

        $needsUpdate = $order->payment_status !== 'paid';

        if ($needsUpdate) {
            $order->payment_status = 'paid';
            $order->status = $order->status === 'cancelled' ? $order->status : 'confirmed';
            $order->payment_order_reference = $orderReference;
            $order->save();

            Log::info('✅ Order marked as paid via webhook', [
                'order_id' => $order->id,
                'order_reference' => $orderReference,
            ]);
        } else {
            Log::info('ℹ️ Order already marked as paid', [
                'order_id' => $order->id,
                'order_reference' => $orderReference,
            ]);
        }

        return response()->json([
            'status' => 'processed',
            'order_id' => $order->id,
            'order_reference' => $orderReference,
            'already_paid' => !$needsUpdate,
        ]);
    }

    private function extractOrderReference(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'orderReference'),
            data_get($payload, 'order.reference'),
            data_get($payload, 'order.id'),
            data_get($payload, 'merchantOrderReference'),
            data_get($payload, 'orderId'),
            data_get($payload, 'id'),
            data_get($payload, 'result.order.id'),
            data_get($payload, 'result.order.reference'),
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    private function isPaymentSuccessful(array $payload): bool
    {
        $successValues = [
            'success',
            'succeeded',
            'successful',
            'paid',
            'completed',
            'captured',
            'approved',
            'authorized',
        ];

        $statusCandidates = [
            data_get($payload, 'event'),
            data_get($payload, 'eventType'),
            data_get($payload, 'event.type'),
            data_get($payload, 'status'),
            data_get($payload, 'paymentStatus'),
            data_get($payload, 'orderStatus'),
            data_get($payload, 'transactionStatus'),
            data_get($payload, 'result.status'),
            data_get($payload, 'result.order.status'),
        ];

        foreach ($statusCandidates as $status) {
            if (is_string($status) && in_array(strtolower($status), $successValues, true)) {
                return true;
            }
        }

        $resultCode = (string) data_get($payload, 'resultCode', data_get($payload, 'responseCode'));
        if (in_array($resultCode, ['000', '0000', '00'], true)) {
            return true;
        }

        return false;
    }
}


