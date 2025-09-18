<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft, User, Store, MapPin, Phone, Clock, DollarSign, Package, Truck,
    CheckCircle, AlertCircle, XCircle, Navigation, Calendar, CreditCard,
    Receipt, Star, Mail, Hash, Timer, Utensils
} from 'lucide-vue-next';

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
            <div class="flex items-center justify-between bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center space-x-4">
                    <Link
                        href="/orders"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-transparent rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to Orders
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">{{ order.order_number }}</h1>
                        <p class="text-blue-100">Order placed on {{ new Date(order.created_at).toLocaleDateString() }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-gray-800"
                        :class="getStatusColor(order.status)"
                    >
                        <CheckCircle v-if="order.status === 'delivered'" class="w-4 h-4 mr-1" />
                        <AlertCircle v-else-if="order.status === 'pending'" class="w-4 h-4 mr-1" />
                        <XCircle v-else-if="order.status === 'cancelled'" class="w-4 h-4 mr-1" />
                        <Clock v-else class="w-4 h-4 mr-1" />
                        {{ order.status.toUpperCase() }}
                    </span>
                    <span
                        v-if="order.shipping_status"
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white bg-opacity-20 text-white"
                    >
                        <Truck class="w-4 h-4 mr-1" />
                        {{ order.shipping_status }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <DollarSign class="w-6 h-6 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Amount</p>
                                <p class="text-2xl font-bold text-gray-900">${{ order.total }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <Package class="w-6 h-6 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Items</p>
                                <p class="text-2xl font-bold text-gray-900">{{ calculations.items_count }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <CreditCard class="w-6 h-6 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Payment</p>
                                <p class="text-lg font-bold text-gray-900 capitalize">{{ order.payment_method }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <Timer class="w-6 h-6 text-orange-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Status</p>
                                <p class="text-lg font-bold text-gray-900 capitalize">{{ order.status }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="xl:col-span-2 space-y-8">
                        <!-- Order Items -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="flex items-center text-xl font-semibold text-gray-900">
                                    <Utensils class="w-6 h-6 mr-3 text-blue-600" />
                                    Order Items
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                        {{ calculations.items_count }} items
                                    </span>
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-6">
                                    <div
                                        v-for="item in order.order_items || order.orderItems"
                                        :key="item.id"
                                        class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200 hover:shadow-md transition-shadow"
                                    >
                                        <div class="flex-shrink-0 w-16 h-16 bg-white rounded-lg border flex items-center justify-center">
                                            <Utensils class="w-8 h-8 text-gray-400" />
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="text-lg font-medium text-gray-900">{{ item.item_name }}</h3>
                                                    <p v-if="item.menu_item?.description || item.menuItem?.description" class="text-sm text-gray-600 mt-1">
                                                        {{ item.menu_item?.description || item.menuItem?.description }}
                                                    </p>
                                                    <div class="flex items-center space-x-6 mt-3">
                                                        <div class="flex items-center text-sm text-gray-500">
                                                            <Hash class="w-4 h-4 mr-1" />
                                                            Qty: <span class="font-medium ml-1">{{ item.quantity }}</span>
                                                        </div>
                                                        <div class="flex items-center text-sm text-gray-500">
                                                            <DollarSign class="w-4 h-4 mr-1" />
                                                            Unit: <span class="font-medium ml-1">${{ item.price }}</span>
                                                        </div>
                                                        <div v-if="item.menu_item?.preparation_time || item.menuItem?.preparation_time" class="flex items-center text-sm text-gray-500">
                                                            <Timer class="w-4 h-4 mr-1" />
                                                            <span class="font-medium">{{ item.menu_item?.preparation_time || item.menuItem?.preparation_time }} min</span>
                                                        </div>
                                                    </div>
                                                    <div v-if="item.special_instructions" class="mt-3 p-2 bg-orange-50 border border-orange-200 rounded-md">
                                                        <p class="text-sm text-orange-800">
                                                            <AlertCircle class="w-4 h-4 inline mr-1" />
                                                            {{ item.special_instructions }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xl font-bold text-gray-900">${{ item.subtotal }}</p>
                                                    <p class="text-sm text-gray-500">Original: ${{ item.menu_item?.price || item.menuItem?.price }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Tracking -->
                        <div v-if="shippingOrder || order.dsp_order_id" class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="flex items-center text-xl font-semibold text-gray-900">
                                    <Truck class="w-6 h-6 mr-3 text-green-600" />
                                    Shipping & Delivery
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-2">
                                            <Hash class="w-5 h-5 text-blue-600 mr-2" />
                                            <h4 class="font-medium text-blue-900">Shipping IDs</h4>
                                        </div>
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-xs text-blue-600">Shop ID</p>
                                                <p class="font-mono text-sm text-blue-900">{{ order.shop_id }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-blue-600">DSP Order ID</p>
                                                <p class="font-mono text-sm text-blue-900">{{ order.dsp_order_id }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div class="flex items-center mb-2">
                                            <CheckCircle class="w-5 h-5 text-green-600 mr-2" />
                                            <h4 class="font-medium text-green-900">Current Status</h4>
                                        </div>
                                        <p class="text-lg font-semibold text-green-900">
                                            {{ order.shipping_status || 'New Order' }}
                                        </p>
                                        <p v-if="shippingOrder" class="text-sm text-green-600 mt-1">
                                            Payment: {{ getPaymentTypeText(shippingOrder.payment_type) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Driver Info -->
                                <div v-if="order.driver_name" class="bg-gray-50 p-6 rounded-lg border">
                                    <h4 class="flex items-center font-medium text-gray-900 mb-4">
                                        <User class="w-5 h-5 mr-2 text-gray-600" />
                                        Driver Information
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-2 bg-white rounded-lg">
                                                <User class="w-5 h-5 text-gray-600" />
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Driver Name</p>
                                                <p class="font-medium text-gray-900">{{ order.driver_name }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <div class="p-2 bg-white rounded-lg">
                                                <Phone class="w-5 h-5 text-gray-600" />
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Driver Phone</p>
                                                <p class="font-medium text-gray-900">{{ order.driver_phone }}</p>
                                            </div>
                                        </div>
                                        <div v-if="order.driver_latitude && order.driver_longitude" class="md:col-span-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="p-2 bg-white rounded-lg">
                                                    <Navigation class="w-5 h-5 text-gray-600" />
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-500">Current Location</p>
                                                    <p class="font-mono text-sm text-gray-900">
                                                        {{ order.driver_latitude }}, {{ order.driver_longitude }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Customer Card -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <User class="w-5 h-5 mr-2 text-blue-600" />
                                    Customer Details
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <User class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Full Name</p>
                                        <p class="font-medium text-gray-900">{{ order.user.name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <Mail class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Email</p>
                                        <p class="font-medium text-gray-900">{{ order.user.email }}</p>
                                    </div>
                                </div>
                                <div v-if="order.user.phone_number || order.user.phone" class="flex items-center space-x-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <Phone class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Phone</p>
                                        <p class="font-medium text-gray-900">{{ order.user.phone_number || order.user.phone }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Restaurant Card -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <Store class="w-5 h-5 mr-2 text-orange-600" />
                                    Restaurant
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <p class="text-lg font-medium text-gray-900">{{ order.restaurant.name }}</p>
                                    <p v-if="order.restaurant.description" class="text-sm text-gray-600 mt-1">
                                        {{ order.restaurant.description }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <MapPin class="w-5 h-5 text-orange-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Address</p>
                                        <p class="text-sm text-gray-900">{{ order.restaurant.address }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <Phone class="w-5 h-5 text-orange-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Phone</p>
                                        <p class="font-medium text-gray-900">{{ order.restaurant.phone }}</p>
                                    </div>
                                </div>
                                <div v-if="order.restaurant.rating" class="flex items-center space-x-3">
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <Star class="w-5 h-5 text-orange-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Rating</p>
                                        <p class="font-medium text-gray-900">{{ order.restaurant.rating }}/5</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Details -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <MapPin class="w-5 h-5 mr-2 text-green-600" />
                                    Delivery Information
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <User class="w-5 h-5 text-green-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Recipient</p>
                                        <p class="font-medium text-gray-900">{{ order.delivery_name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <Phone class="w-5 h-5 text-green-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Contact</p>
                                        <p class="font-medium text-gray-900">{{ order.delivery_phone }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <MapPin class="w-5 h-5 text-green-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Address</p>
                                        <p class="text-sm text-gray-900 leading-relaxed">{{ order.delivery_address }}</p>
                                    </div>
                                </div>
                                <div v-if="order.special_instructions" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div class="flex items-start space-x-2">
                                        <AlertCircle class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-sm font-medium text-yellow-800">Special Instructions</p>
                                            <p class="text-sm text-yellow-700 mt-1">{{ order.special_instructions }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <Receipt class="w-5 h-5 mr-2 text-purple-600" />
                                    Order Summary
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">Items Subtotal</span>
                                        <span class="font-medium text-gray-900">${{ calculations.items_subtotal }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">Order Subtotal</span>
                                        <span class="font-medium text-gray-900">${{ order.subtotal }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">Delivery Fee</span>
                                        <span class="font-medium text-gray-900">${{ order.delivery_fee }}</span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">Tax</span>
                                        <span class="font-medium text-gray-900">${{ order.tax }}</span>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xl font-bold text-gray-900">Total Amount</span>
                                            <span class="text-2xl font-bold text-green-600">${{ order.total }}</span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg mt-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center space-x-2">
                                                <CreditCard class="w-5 h-5 text-gray-600" />
                                                <span class="text-sm text-gray-600">Payment Method</span>
                                            </div>
                                            <span class="font-medium text-gray-900 capitalize">{{ order.payment_method }}</span>
                                        </div>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-sm text-gray-600">Payment Status</span>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="order.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                            >
                                                {{ order.payment_status.toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <Calendar class="w-5 h-5 mr-2 text-indigo-600" />
                                    Order Timeline
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-4 p-3 bg-blue-50 rounded-lg">
                                        <div class="p-2 bg-blue-100 rounded-full">
                                            <Calendar class="w-4 h-4 text-blue-600" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-blue-900">Order Created</p>
                                            <p class="text-sm text-blue-700">{{ new Date(order.created_at).toLocaleString() }}</p>
                                        </div>
                                    </div>
                                    <div v-if="order.estimated_delivery_time" class="flex items-center space-x-4 p-3 bg-yellow-50 rounded-lg">
                                        <div class="p-2 bg-yellow-100 rounded-full">
                                            <Clock class="w-4 h-4 text-yellow-600" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-yellow-900">Estimated Delivery</p>
                                            <p class="text-sm text-yellow-700">{{ new Date(order.estimated_delivery_time).toLocaleString() }}</p>
                                        </div>
                                    </div>
                                    <div v-if="order.delivered_at" class="flex items-center space-x-4 p-3 bg-green-50 rounded-lg">
                                        <div class="p-2 bg-green-100 rounded-full">
                                            <CheckCircle class="w-4 h-4 text-green-600" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-green-900">Delivered</p>
                                            <p class="text-sm text-green-700">{{ new Date(order.delivered_at).toLocaleString() }}</p>
                                        </div>
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
