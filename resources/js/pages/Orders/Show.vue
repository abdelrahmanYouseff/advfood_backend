<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
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
    customer_latitude?: number;
    customer_longitude?: number;
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

// Debug: Log order data to check location
console.log('Order data:', {
    customer_latitude: props.order.customer_latitude,
    customer_longitude: props.order.customer_longitude,
    shippingOrder: props.shippingOrder
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'لوحة التحكم',
        href: '/dashboard',
    },
    {
        title: 'الطلبات',
        href: '/orders',
    },
    {
        title: `طلب #${props.order.order_number}`,
        href: `/orders/${props.order.id}`,
    },
];

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        confirmed: 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200',
        preparing: 'bg-gray-300 text-gray-900 dark:bg-gray-600 dark:text-gray-100',
        ready: 'bg-gray-400 text-gray-900 dark:bg-gray-500 dark:text-gray-100',
        delivering: 'bg-gray-500 text-white dark:bg-gray-400 dark:text-gray-900',
        delivered: 'bg-gray-600 text-white dark:bg-gray-300 dark:text-gray-900',
        cancelled: 'bg-gray-700 text-white dark:bg-gray-200 dark:text-gray-900',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getPaymentTypeText = (type: number) => {
    const types: Record<number, string> = {
        0: 'دفع مسبق',
        1: 'الدفع عند الاستلام',
        10: 'آلة البطاقات',
    };
    return types[type] || 'غير معروف';
};

const getStatusText = (status: string) => {
    const statusMap: Record<string, string> = {
        pending: 'قيد الانتظار',
        confirmed: 'مؤكد',
        preparing: 'قيد التحضير',
        ready: 'جاهز',
        delivering: 'قيد التوصيل',
        delivered: 'تم التسليم',
        cancelled: 'ملغي',
    };
    return statusMap[status.toLowerCase()] || status;
};

const getShippingStatusText = (status?: string) => {
    if (!status) return 'طلب جديد';
    const statusMap: Record<string, string> = {
        'New Order': 'طلب جديد',
        'Confirmed': 'مؤكد',
        'Preparing': 'قيد التحضير',
        'Ready': 'جاهز',
        'Out for Delivery': 'خارج للتوصيل',
        'Delivered': 'تم التسليم',
        'Cancelled': 'ملغي',
    };
    return statusMap[status] || status;
};

const getPaymentMethodText = (method: string) => {
    const methods: Record<string, string> = {
        cash: 'نقدي',
        card: 'بطاقة',
        online: 'أونلاين',
    };
    return methods[method.toLowerCase()] || method;
};

// Helper function to check if order is delivered
const isDelivered = (order: any) => {
    return order.shipping_status === 'Delivered' ||
           order.shipping_status?.toLowerCase() === 'delivered';
};

// Order status options - Black and gray only
const statusOptions = [
    { value: 'pending', label: 'قيد الانتظار', color: 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700', icon: AlertCircle },
    { value: 'confirmed', label: 'مؤكد', color: 'bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600', icon: CheckCircle },
    { value: 'preparing', label: 'قيد التحضير', color: 'bg-gray-300 text-gray-900 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-100 dark:hover:bg-gray-500', icon: Clock },
    { value: 'ready', label: 'جاهز', color: 'bg-gray-400 text-gray-900 hover:bg-gray-500 dark:bg-gray-500 dark:text-gray-100 dark:hover:bg-gray-400', icon: CheckCircle },
    { value: 'delivering', label: 'قيد التوصيل', color: 'bg-gray-500 text-white hover:bg-gray-600 dark:bg-gray-400 dark:text-gray-900 dark:hover:bg-gray-300', icon: Truck },
    { value: 'delivered', label: 'تم التسليم', color: 'bg-gray-600 text-white hover:bg-gray-700 dark:bg-gray-300 dark:text-gray-900 dark:hover:bg-gray-200', icon: CheckCircle },
    { value: 'cancelled', label: 'ملغي', color: 'bg-gray-700 text-white hover:bg-gray-800 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-gray-100', icon: XCircle },
];

// Update order status
const updateOrderStatus = (status: string) => {
    if (confirm(`هل أنت متأكد من تغيير حالة الطلب إلى "${statusOptions.find(s => s.value === status)?.label}"؟`)) {
        router.post(route('orders.update-status', props.order.id), {
            status: status,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Status will be updated via Inertia response
            },
        });
    }
};

// Get restaurant image URL
const getRestaurantImage = (restaurantName: string): string | null => {
    if (typeof window === 'undefined') return null;
    const baseUrl = window.location.origin;
    
    // Map restaurant names to their logo files
    const restaurantLogos: Record<string, string> = {
        'Tant Bakiza': '/tant-bakiza-logo.png',
        'Delawa': '/delawa-logo.png',
        'Gather Us': '/gatherus-logo.png',
    };
    
    const logoPath = restaurantLogos[restaurantName];
    return logoPath ? `${baseUrl}${logoPath}` : null;
};

// Format currency professionally (English, small SAR above amount)
const formatCurrencyProfessional = (amount: number | string) => {
    const numeric = Number(amount ?? 0);
    // Format the number without the currency symbol
    const formattedAmount = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numeric);
    return formattedAmount;
};
</script>

<template>
    <Head :title="`طلب #${order.order_number}`" />

    <AppLayout>
        <template #header>
            <div :class="[
                'flex items-center justify-between text-white p-6 rounded-lg shadow-lg',
                isDelivered(order)
                    ? 'bg-gradient-to-r from-gray-500 to-gray-600 opacity-75'
                    : 'bg-gradient-to-r from-gray-700 to-gray-800'
            ]">
                <div class="flex items-center space-x-4">
                    <Link
                        href="/orders"
                        :class="[
                            'inline-flex items-center px-4 py-2 text-sm font-medium border border-transparent rounded-lg transition-colors',
                            isDelivered(order)
                                ? 'text-gray-600 bg-gray-200 hover:bg-gray-300'
                                : 'text-gray-800 bg-white hover:bg-gray-50'
                        ]"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        العودة للطلبات
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">{{ order.order_number }}</h1>
                        <p :class="[
                            'text-sm',
                            isDelivered(order)
                                ? 'text-gray-200'
                                : 'text-gray-200'
                        ]">تم إنشاء الطلب في {{ new Date(order.created_at).toLocaleDateString('ar-SA') }}</p>
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
                        {{ getStatusText(order.status) }}
                    </span>
                    <span
                        v-if="order.shipping_status"
                        :class="[
                            'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                            isDelivered(order)
                                ? 'bg-gray-300 bg-opacity-30 text-gray-200'
                                : 'bg-white bg-opacity-20 text-white'
                        ]"
                    >
                        <Truck class="w-4 h-4 mr-1" />
                        {{ getShippingStatusText(order.shipping_status) }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Order Number -->
                <div class="mb-6">
                    <h2 class="text-4xl font-bold text-gray-900">{{ order.order_number }}</h2>
                </div>
                
                <!-- Quick Stats -->
                <div :class="[
                    'grid grid-cols-1 md:grid-cols-4 gap-4 mb-8',
                    isDelivered(order) ? 'opacity-75 grayscale-[0.2]' : ''
                ]">
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-200 rounded-lg">
                                <DollarSign class="w-6 h-6 text-gray-700" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">المبلغ الإجمالي</p>
                                <div class="flex items-baseline gap-1">
                                    <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                    <span class="text-2xl font-bold text-gray-900">{{ formatCurrencyProfessional(order.total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-200 rounded-lg">
                                <Package class="w-6 h-6 text-gray-700" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">العناصر</p>
                                <p class="text-2xl font-bold text-gray-900">{{ calculations.items_count }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-200 rounded-lg">
                                <CreditCard class="w-6 h-6 text-gray-700" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">طريقة الدفع</p>
                                <p class="text-lg font-bold text-gray-900">{{ getPaymentMethodText(order.payment_method) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm border">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-200 rounded-lg">
                                <Timer class="w-6 h-6 text-gray-700" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">الحالة</p>
                                <p class="text-lg font-bold text-gray-900">{{ getStatusText(order.status) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Order Status Section -->
                <div class="bg-white shadow-sm rounded-lg border mb-8">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="flex items-center text-lg font-semibold text-gray-900">
                            <Clock class="w-5 h-5 mr-2 text-gray-700" />
                            تعديل حالة الطلب
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">اختر حالة جديدة للطلب</p>
                    </div>
                    <div class="p-6">
                        <div v-if="order.status === 'delivered' || isDelivered(order)" class="bg-gray-100 border border-gray-300 rounded-lg p-4 mb-4">
                            <div class="flex items-center">
                                <CheckCircle class="w-5 h-5 text-gray-700 mr-2" />
                                <p class="text-sm font-medium text-gray-800">
                                    تم تسليم الطلب - لا يمكن تعديل الحالة بعد التسليم
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button
                                v-for="statusOption in statusOptions"
                                :key="statusOption.value"
                                @click="updateOrderStatus(statusOption.value)"
                                :disabled="order.status === 'delivered' || isDelivered(order)"
                                :class="[
                                    'inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                                    order.status === 'delivered' || isDelivered(order)
                                        ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400'
                                        : [
                                            statusOption.color,
                                            'cursor-pointer',
                                            order.status === statusOption.value
                                                ? 'ring-2 ring-offset-2 ring-gray-600 shadow-lg scale-105'
                                                : 'shadow-sm hover:shadow-md hover:scale-102'
                                        ]
                                ]"
                            >
                                <component :is="statusOption.icon" class="w-4 h-4 mr-2" />
                                {{ statusOption.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="[
                    'grid grid-cols-1 xl:grid-cols-3 gap-8',
                    isDelivered(order) ? 'opacity-80 grayscale-[0.1]' : ''
                ]">
                    <!-- Main Content -->
                    <div class="xl:col-span-2 space-y-8">
                        <!-- Order Items -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="flex items-center text-xl font-semibold text-gray-900">
                                    <Utensils class="w-6 h-6 mr-3 text-gray-700" />
                                    عناصر الطلب
                                    <span class="ml-2 px-2 py-1 text-xs bg-gray-200 text-gray-800 rounded-full">
                                        {{ calculations.items_count }} عنصر
                                    </span>
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="space-y-6">
                                    <div
                                        v-for="item in order.order_items"
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
                                                    <p v-if="item.menu_item?.description" class="text-sm text-gray-600 mt-1">
                                                        {{ item.menu_item?.description }}
                                                    </p>
                                                    <div class="flex items-center space-x-6 mt-3">
                                                        <div class="flex items-center text-sm text-gray-500">
                                                            <Hash class="w-4 h-4 mr-1" />
                                                            الكمية: <span class="font-medium ml-1">{{ item.quantity }}</span>
                                                        </div>
                                                        <div class="flex items-center text-sm text-gray-500">
                                                            السعر: 
                                                            <span class="font-medium ml-1 flex items-baseline gap-0.5">
                                                                <sup class="text-[10px] font-semibold text-gray-600">SAR</sup>
                                                                {{ formatCurrencyProfessional(item.price) }}
                                                            </span>
                                                        </div>
                                                        <div v-if="item.menu_item?.preparation_time" class="flex items-center text-sm text-gray-500">
                                                            <Timer class="w-4 h-4 mr-1" />
                                                            <span class="font-medium">{{ item.menu_item?.preparation_time }} دقيقة</span>
                                                        </div>
                                                    </div>
                                                    <div v-if="item.special_instructions" class="mt-3 p-2 bg-gray-100 border border-gray-300 rounded-md">
                                                        <p class="text-sm text-gray-800">
                                                            <AlertCircle class="w-4 h-4 inline mr-1" />
                                                            {{ item.special_instructions }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="flex items-baseline gap-0.5">
                                                        <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                                        <span class="text-xl font-bold text-gray-900">{{ formatCurrencyProfessional(item.subtotal) }}</span>
                                                </div>
                                                    <p class="text-sm text-gray-500 flex items-baseline gap-0.5">
                                                        الأصلي: 
                                                        <sup class="text-[10px] font-semibold text-gray-600">SAR</sup>
                                                        {{ formatCurrencyProfessional(item.menu_item?.price) }}
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
                        <!-- Restaurant Logo Only (Circular, No Card) -->
                        <div class="flex items-center justify-center">
                            <img 
                                v-if="getRestaurantImage(order.restaurant.name)"
                                :src="getRestaurantImage(order.restaurant.name) || ''" 
                                :alt="order.restaurant.name"
                                class="h-32 w-32 rounded-full object-cover"
                                @error="(e) => { 
                                    console.error('Failed to load restaurant image:', e.target.src);
                                    e.target.style.display = 'none';
                                }"
                                @load="(e) => {
                                    console.log('Restaurant image loaded successfully:', e.target.src);
                                }"
                            />
                            <!-- Fallback: Show icon if no logo (Circular) -->
                            <div v-else class="flex h-32 w-32 items-center justify-center rounded-full bg-gray-200">
                                <Store class="h-16 w-16 text-gray-400" />
                            </div>
                        </div>

                        <!-- Customer Card (Delivery Details) -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <User class="w-5 h-5 mr-2 text-gray-700" />
                                    تفاصيل العميل
                                </h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-gray-200 rounded-lg">
                                        <User class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">المستلم</p>
                                        <p class="font-medium text-gray-900">{{ order.delivery_name }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-gray-200 rounded-lg">
                                        <Phone class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">الاتصال</p>
                                        <p class="font-medium text-gray-900">{{ order.delivery_phone }}</p>
                                    </div>
                                </div>
                                <div v-if="order.delivery_address" class="flex items-start space-x-3">
                                    <div class="p-2 bg-gray-200 rounded-lg">
                                        <MapPin class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">العنوان</p>
                                        <p class="text-sm text-gray-900 leading-relaxed">{{ order.delivery_address }}</p>
                                    </div>
                                </div>
                                <!-- Customer Location -->
                                <div v-if="order.customer_latitude && order.customer_longitude" class="flex items-start space-x-3">
                                    <div class="p-2 bg-gray-200 rounded-lg">
                                        <Navigation class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-500 mb-1">الموقع (Location)</p>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-mono text-sm text-gray-900 bg-gray-50 px-3 py-1.5 rounded border">
                                                {{ order.customer_latitude }}, {{ order.customer_longitude }}
                                            </p>
                                            <a
                                                :href="`https://www.google.com/maps?q=${order.customer_latitude},${order.customer_longitude}`"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-gray-800 rounded hover:bg-gray-900 transition"
                                            >
                                                <MapPin class="w-3.5 h-3.5" />
                                                فتح في Google Maps
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Shipping Order Location (fallback) -->
                                <div v-else-if="shippingOrder?.latitude && shippingOrder?.longitude" class="flex items-start space-x-3">
                                    <div class="p-2 bg-gray-200 rounded-lg">
                                        <Navigation class="w-5 h-5 text-gray-700" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-500 mb-1">الموقع (Location)</p>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-mono text-sm text-gray-900 bg-gray-50 px-3 py-1.5 rounded border">
                                                {{ shippingOrder.latitude }}, {{ shippingOrder.longitude }}
                                            </p>
                                            <a
                                                :href="`https://www.google.com/maps?q=${shippingOrder.latitude},${shippingOrder.longitude}`"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-gray-800 rounded hover:bg-gray-900 transition"
                                            >
                                                <MapPin class="w-3.5 h-3.5" />
                                                فتح في Google Maps
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="bg-white shadow-sm rounded-lg border">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="flex items-center text-lg font-semibold text-gray-900">
                                    <Receipt class="w-5 h-5 mr-2 text-gray-700" />
                                    ملخص الطلب
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">المجموع الفرعي للعناصر</span>
                                        <span class="font-medium text-gray-900 flex items-baseline gap-0.5">
                                            <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                            {{ formatCurrencyProfessional(calculations.items_subtotal) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">المجموع الفرعي للطلب</span>
                                        <span class="font-medium text-gray-900 flex items-baseline gap-0.5">
                                            <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                            {{ formatCurrencyProfessional(order.subtotal) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">رسوم التوصيل</span>
                                        <span class="font-medium text-gray-900 flex items-baseline gap-0.5">
                                            <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                            {{ formatCurrencyProfessional(order.delivery_fee) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center py-2">
                                        <span class="text-gray-600">الضريبة</span>
                                        <span class="font-medium text-gray-900 flex items-baseline gap-0.5">
                                            <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                            {{ formatCurrencyProfessional(order.tax) }}
                                        </span>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xl font-bold text-gray-900">المبلغ الإجمالي</span>
                                            <span class="text-2xl font-bold text-gray-700 flex items-baseline gap-1">
                                                <sup class="text-xs font-semibold text-gray-600">SAR</sup>
                                                {{ formatCurrencyProfessional(order.total) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 p-4 rounded-lg mt-4">
                                        <div class="flex justify-between items-center">
                                            <div class="flex items-center space-x-2">
                                                <CreditCard class="w-5 h-5 text-gray-600" />
                                                <span class="text-sm text-gray-600">طريقة الدفع</span>
                                            </div>
                                            <span class="font-medium text-gray-900">{{ getPaymentMethodText(order.payment_method) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center mt-2">
                                            <span class="text-sm text-gray-600">حالة الدفع</span>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="order.payment_status === 'paid' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-700'"
                                            >
                                                {{ order.payment_status === 'paid' ? 'مدفوع' : order.payment_status === 'pending' ? 'قيد الانتظار' : 'فاشل' }}
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
                                    <Calendar class="w-5 h-5 mr-2 text-gray-700" />
                                    الخط الزمني للطلب
                                </h3>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-4 p-3 bg-gray-100 rounded-lg">
                                        <div class="p-2 bg-gray-200 rounded-full">
                                            <Calendar class="w-4 h-4 text-gray-700" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">تم إنشاء الطلب</p>
                                            <p class="text-sm text-gray-700">{{ new Date(order.created_at).toLocaleString('ar-SA') }}</p>
                                        </div>
                                    </div>
                                    <div v-if="order.estimated_delivery_time" class="flex items-center space-x-4 p-3 bg-gray-100 rounded-lg">
                                        <div class="p-2 bg-gray-200 rounded-full">
                                            <Clock class="w-4 h-4 text-gray-700" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">وقت التوصيل المتوقع</p>
                                            <p class="text-sm text-gray-700">{{ new Date(order.estimated_delivery_time).toLocaleString('ar-SA') }}</p>
                                        </div>
                                    </div>
                                    <div v-if="order.delivered_at" class="flex items-center space-x-4 p-3 bg-gray-100 rounded-lg">
                                        <div class="p-2 bg-gray-200 rounded-full">
                                            <CheckCircle class="w-4 h-4 text-gray-700" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">تم التسليم</p>
                                            <p class="text-sm text-gray-700">{{ new Date(order.delivered_at).toLocaleString('ar-SA') }}</p>
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
