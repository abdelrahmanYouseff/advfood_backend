<?php

namespace App\Contracts;

use Illuminate\Http\Request;

interface ShippingServiceInterface
{
    /**
     * Create an order with the shipping provider
     *
     * @param mixed $order Order object or array
     * @return array|null Response from shipping provider
     */
    public function createOrder($order): ?array;

    /**
     * Get order status from shipping provider
     *
     * @param string $shippingOrderId
     * @return array|null
     */
    public function getOrderStatus(string $shippingOrderId): ?array;

    /**
     * Cancel an order with the shipping provider
     *
     * @param string $shippingOrderId
     * @return bool|array
     */
    public function cancelOrder(string $shippingOrderId);

    /**
     * Handle webhook from shipping provider
     *
     * @param Request $request
     * @return void
     */
    public function handleWebhook(Request $request): void;
}

