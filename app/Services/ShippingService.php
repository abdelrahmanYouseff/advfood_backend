<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    protected $apiBaseUrl;
    protected $apiKey;
    protected $endpoints;
    protected $sendAsForm;

    public function __construct()
    {
        $this->apiBaseUrl = Config::get('services.shipping.url');
        $this->apiKey = Config::get('services.shipping.key');
        $this->endpoints = Config::get('services.shipping.endpoints', [
            'create' => '/orders',
            'status' => '/orders/{id}',
            'cancel' => '/orders/{id}/cancel',
        ]);
        $this->sendAsForm = (bool) Config::get('services.shipping.send_as_form', false);
    }

    public function createOrder($order)
    {
        try {
            if (empty($this->apiBaseUrl) || empty($this->apiKey)) {
                Log::error('Shipping API credentials missing');
                return null;
            }

            $orderObj = is_array($order) ? (object) $order : $order;
            $orderIdString = (string) ($orderObj->order_number ?? $orderObj->id ?? '');
            $shopIdString = isset($orderObj->shop_id) ? (string) $orderObj->shop_id : null;

            if ($orderIdString === '' || empty($shopIdString)) {
                Log::error('Shipping order creation aborted: missing order id or shop_id');
                return null;
            }

            $latitude = isset($orderObj->latitude) ? $orderObj->latitude : null;
            $longitude = isset($orderObj->longitude) ? $orderObj->longitude : null;

            $payload = [
                'id' => $orderIdString,
                'shop_id' => $shopIdString,
                'delivery_details' => [
                    'name' => $orderObj->delivery_name ?? null,
                    'phone' => $orderObj->delivery_phone ?? null,
                    'coordinate' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                    'address' => $orderObj->delivery_address ?? null,
                ],
                'order' => [
                    'payment_type' => $this->mapPaymentType($orderObj->payment_method ?? null),
                    'total' => (float) ($orderObj->total ?? 0),
                    'notes' => $orderObj->special_instructions ?? null,
                ],
            ];

            $url = $this->buildUrl($this->endpoints['create']);

            $request = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ]);

            $response = $this->sendAsForm
                ? $request->asForm()->post($url, $this->flattenArray($payload))
                : $request->withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);

            if (!$response || $response->failed()) {
                Log::error('Failed to create shipping order', [
                    'status' => $response ? $response->status() : null,
                    'body' => $response ? $response->json() : null,
                    'url' => $url,
                ]);
                return null;
            }

            $data = $response->json();

            $dspOrderId = $data['dsp_order_id'] ?? $data['data']['dsp_order_id'] ?? $data['id'] ?? null;
            $shippingStatus = $data['status'] ?? $data['data']['status'] ?? 'New Order';

            $row = [
                'order_id' => $orderObj->id,
                'shop_id' => $orderObj->shop_id ?? null,
                'dsp_order_id' => $dspOrderId,
                'shipping_status' => $shippingStatus,
                'recipient_name' => $orderObj->delivery_name ?? '',
                'recipient_phone' => $orderObj->delivery_phone ?? '',
                'recipient_address' => $orderObj->delivery_address ?? '',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'driver_name' => null,
                'driver_phone' => null,
                'driver_latitude' => null,
                'driver_longitude' => null,
                'total' => (float) ($orderObj->total ?? 0),
                'payment_type' => $this->mapPaymentType($orderObj->payment_method ?? null),
                'notes' => $orderObj->special_instructions ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('shipping_orders')->insert($row);
            return $row;
        } catch (\Throwable $e) {
            Log::error('Exception during shipping order creation', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function getOrderStatus($shippingOrderId)
    {
        try {
            if (empty($this->apiBaseUrl) || empty($this->apiKey) || empty($shippingOrderId)) {
                return null;
            }

            $url = $this->buildUrl($this->endpoints['status'], ['id' => $shippingOrderId]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if (!$response || $response->failed()) {
                Log::warning('Failed to get shipping order status', [
                    'dsp_order_id' => $shippingOrderId,
                    'status' => $response ? $response->status() : null,
                    'body' => $response ? $response->body() : null,
                    'url' => $url,
                ]);
                return null;
            }

            $data = $response->json();

            $status = $data['status'] ?? ($data['data']['status'] ?? null);
            $dspId = $data['dsp_order_id'] ?? ($data['data']['dsp_order_id'] ?? null);
            $driver = $data['driver'] ?? ($data['data']['driver'] ?? null);
            $driverName = is_array($driver) ? ($driver['name'] ?? null) : null;
            $driverPhone = is_array($driver) ? ($driver['phone'] ?? null) : null;
            $driverLat = is_array($driver) ? ($driver['location']['latitude'] ?? null) : null;
            $driverLng = is_array($driver) ? ($driver['location']['longitude'] ?? null) : null;

            $update = array_filter([
                'shipping_status' => $status,
                'driver_name' => $driverName,
                'driver_phone' => $driverPhone,
                'driver_latitude' => $driverLat,
                'driver_longitude' => $driverLng,
                'updated_at' => now(),
            ], fn($v) => !is_null($v));

            if (!empty($update)) {
                DB::table('shipping_orders')
                    ->where('dsp_order_id', $dspId ?: $shippingOrderId)
                    ->update($update);
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('Exception while fetching shipping order status', ['dsp_order_id' => $shippingOrderId, 'message' => $e->getMessage()]);
            return null;
        }
    }

    public function handleWebhook(Request $request): void
    {
        try {
            $payload = $request->all();
            $dspOrderId = $payload['dsp_order_id'] ?? $payload['order_id'] ?? $payload['id'] ?? null;
            if (!$dspOrderId) { return; }

            $updates = array_filter([
                'shipping_status' => $payload['status'] ?? null,
                'driver_name' => $payload['driver']['name'] ?? null,
                'driver_phone' => $payload['driver']['phone'] ?? null,
                'driver_latitude' => $payload['driver']['location']['latitude'] ?? null,
                'driver_longitude' => $payload['driver']['location']['longitude'] ?? null,
                'updated_at' => now(),
            ], fn($v) => !is_null($v));

            if (!empty($updates)) {
                DB::table('shipping_orders')->where('dsp_order_id', $dspOrderId)->update($updates);
            }
        } catch (\Throwable $e) {
            Log::error('Exception while handling shipping webhook', ['message' => $e->getMessage()]);
        }
    }

    protected function mapPaymentType($paymentMethod): int
    {
        $normalized = is_string($paymentMethod) ? strtolower($paymentMethod) : $paymentMethod;
        if ($normalized === 'cash' || $normalized === 1) { return 1; }
        if ($normalized === 'machine' || $normalized === 10) { return 10; }
        return 0;
    }

    protected function buildUrl(string $endpointTemplate, array $params = []): string
    {
        $path = $endpointTemplate;
        foreach ($params as $key => $value) { $path = str_replace('{' . $key . '}', urlencode($value), $path); }
        return rtrim($this->apiBaseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value)) { $result += $this->flattenArray($value, $newKey); }
            else { $result[$newKey] = $value; }
        }
        return $result;
    }
}


