<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, User, Store, MapPin, Phone, Clock, DollarSign, Package, Truck } from 'lucide-vue-next';

interface OrderItem {
    id: number;
    menu_item_id: number;
    item_name: string;
    quantity: number;
    price: string;
    subtotal: string;
    special_instructions?: string;
    menu_item: {
        name: string;
        description: string;
        price: string;
        image?: string;
        preparation_time: number;
        ingredients?: string;
        allergens?: string;
    };
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    shop_id?: string;
    dsp_order_id?: string;
    shipping_status?: string;
    driver_name?: string;
    driver_phone?: string;
    driver_latitude?: number;
    driver_longitude?: number;
    subtotal: string;
    delivery_fee: string;
    tax: string;
    total: string;
    delivery_address: string;
    delivery_phone: string;
    delivery_name: string;
    special_instructions?: string;
    payment_method: string;
    payment_status: string;
    estimated_delivery_time?: string;
    delivered_at?: string;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
        email: string;
        phone_number?: string;
        phone?: string;
        role: string;
    };
    restaurant: {
        id: number;
        name: string;
        description?: string;
        address: string;
        phone: string;
        email?: string;
        delivery_fee: string;
        delivery_time: number;
        rating?: number;
    };
    order_items: OrderItem[];
}

interface ShippingOrder {
    id: number;
    order_id: number;
    shop_id: string;
    dsp_order_id: string;
    shipping_status: string;
    recipient_name: string;
    recipient_phone: string;
    recipient_address: string;
    latitude?: number;
    longitude?: number;
    driver_name?: string;
    driver_phone?: string;
    driver_latitude?: number;
    driver_longitude?: number;
    total: string;
    payment_type: number;
    notes?: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    order: Order;
    shippingOrder?: ShippingOrder;
    calculations: {
        items_subtotal: string;
        items_count: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Orders',
        href: '/orders',
    },
    {
        title: `Order #${props.order.order_number}`,
    },
];

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        preparing: 'bg-orange-100 text-orange-800',
        ready: 'bg-green-100 text-green-800',
        delivering: 'bg-purple-100 text-purple-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getPaymentTypeText = (type: number) => {
    const types: Record<number, string> = {
        0: 'Prepaid',
        1: 'Cash on Delivery',
        10: 'Card Machine',
    };
    return types[type] || 'Unknown';
};
</script>

<template>
    <Head :title="`Order #${order.order_number}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        href="/orders"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to Orders
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Order Details: {{ order.order_number }}
                    </h2>
                </div>
                <div class="flex items-center space-x-2">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getStatusColor(order.status)"
                    >
                        {{ order.status }}
                    </span>
                    <span
                        v-if="order.shipping_status"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                        <Truck class="w-3 h-3 mr-1" />
                        {{ order.shipping_status }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Order Info -->
                    <div class="lg:col-span-2">
                        <!-- Order Items -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg mb-6">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <Package class="w-5 h-5 mr-2" />
                                    Order Items ({{ calculations.items_count }} items)
                                </h3>
                                <div class="space-y-4">
                                    <div
                                        v-for="item in order.order_items"
                                        :key="item.id"
                                        class="flex items-start justify-between p-4 border border-gray-200 rounded-lg"
                                    >
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">{{ item.item_name }}</h4>
                                            <p v-if="item.menu_item.description" class="text-sm text-gray-600 mt-1">
                                                {{ item.menu_item.description }}
                                            </p>
                                            <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                                <span>Qty: {{ item.quantity }}</span>
                                                <span>Unit Price: ${{ item.price }}</span>
                                                <span v-if="item.menu_item.preparation_time">
                                                    <Clock class="w-4 h-4 inline mr-1" />
                                                    {{ item.menu_item.preparation_time }} min
                                                </span>
                                            </div>
                                            <p v-if="item.special_instructions" class="text-sm text-orange-600 mt-2">
                                                Note: {{ item.special_instructions }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium text-gray-900">${{ item.subtotal }}</p>
                                            <p class="text-sm text-gray-500">Original: ${{ item.menu_item.price }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Details -->
                        <div v-if="shippingOrder || order.dsp_order_id" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <Truck class="w-5 h-5 mr-2" />
                                    Shipping Details
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Shop ID</p>
                                        <p class="text-gray-900">{{ order.shop_id }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Shipping Order ID</p>
                                        <p class="text-gray-900">{{ order.dsp_order_id }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Shipping Status</p>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                        >
                                            {{ order.shipping_status || 'New Order' }}
                                        </span>
                                    </div>
                                    <div v-if="shippingOrder">
                                        <p class="text-sm font-medium text-gray-500">Payment Type</p>
                                        <p class="text-gray-900">{{ getPaymentTypeText(shippingOrder.payment_type) }}</p>
                                    </div>
                                </div>

                                <!-- Driver Details -->
                                <div v-if="order.driver_name" class="mt-6 p-4 bg-gray-50 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-3">Driver Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Driver Name</p>
                                            <p class="text-gray-900">{{ order.driver_name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Driver Phone</p>
                                            <p class="text-gray-900">{{ order.driver_phone }}</p>
                                        </div>
                                        <div v-if="order.driver_latitude && order.driver_longitude" class="md:col-span-2">
                                            <p class="text-sm font-medium text-gray-500">Driver Location</p>
                                            <p class="text-gray-900">
                                                {{ order.driver_latitude }}, {{ order.driver_longitude }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Customer Info -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <User class="w-5 h-5 mr-2" />
                                    Customer
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Name</p>
                                        <p class="text-gray-900">{{ order.user.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Email</p>
                                        <p class="text-gray-900">{{ order.user.email }}</p>
                                    </div>
                                    <div v-if="order.user.phone_number || order.user.phone">
                                        <p class="text-sm font-medium text-gray-500">Phone</p>
                                        <p class="text-gray-900">{{ order.user.phone_number || order.user.phone }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Role</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ order.user.role }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Restaurant Info -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <Store class="w-5 h-5 mr-2" />
                                    Restaurant
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Name</p>
                                        <p class="text-gray-900">{{ order.restaurant.name }}</p>
                                    </div>
                                    <div v-if="order.restaurant.description">
                                        <p class="text-sm font-medium text-gray-500">Description</p>
                                        <p class="text-gray-900">{{ order.restaurant.description }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Address</p>
                                        <p class="text-gray-900">{{ order.restaurant.address }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Phone</p>
                                        <p class="text-gray-900">{{ order.restaurant.phone }}</p>
                                    </div>
                                    <div v-if="order.restaurant.rating">
                                        <p class="text-sm font-medium text-gray-500">Rating</p>
                                        <p class="text-gray-900">{{ order.restaurant.rating }}/5</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Info -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <MapPin class="w-5 h-5 mr-2" />
                                    Delivery
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Recipient</p>
                                        <p class="text-gray-900">{{ order.delivery_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Phone</p>
                                        <p class="text-gray-900">{{ order.delivery_phone }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Address</p>
                                        <p class="text-gray-900">{{ order.delivery_address }}</p>
                                    </div>
                                    <div v-if="order.special_instructions">
                                        <p class="text-sm font-medium text-gray-500">Special Instructions</p>
                                        <p class="text-gray-900">{{ order.special_instructions }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <DollarSign class="w-5 h-5 mr-2" />
                                    Price Breakdown
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Items Subtotal</span>
                                        <span class="text-gray-900">${{ calculations.items_subtotal }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Order Subtotal</span>
                                        <span class="text-gray-900">${{ order.subtotal }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Delivery Fee</span>
                                        <span class="text-gray-900">${{ order.delivery_fee }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tax</span>
                                        <span class="text-gray-900">${{ order.tax }}</span>
                                    </div>
                                    <div class="flex justify-between pt-3 border-t font-medium">
                                        <span class="text-gray-900">Total</span>
                                        <span class="text-gray-900">${{ order.total }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Timeline -->
                        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="flex items-center text-lg font-medium text-gray-900 mb-4">
                                    <Clock class="w-5 h-5 mr-2" />
                                    Timeline
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Created</p>
                                        <p class="text-gray-900">{{ new Date(order.created_at).toLocaleString() }}</p>
                                    </div>
                                    <div v-if="order.estimated_delivery_time">
                                        <p class="text-sm font-medium text-gray-500">Estimated Delivery</p>
                                        <p class="text-gray-900">{{ new Date(order.estimated_delivery_time).toLocaleString() }}</p>
                                    </div>
                                    <div v-if="order.delivered_at">
                                        <p class="text-sm font-medium text-gray-500">Delivered</p>
                                        <p class="text-gray-900">{{ new Date(order.delivered_at).toLocaleString() }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Payment Method</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ order.payment_method }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Payment Status</p>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                        >
                                            {{ order.payment_status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
