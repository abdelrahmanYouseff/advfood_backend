<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    Store,
    ShoppingCart,
    DollarSign,
    Clock,
    TrendingUp,
    Package,
    Calendar
} from 'lucide-vue-next';

interface Props {
    stats: {
        total_restaurants: number;
        total_orders: number;
        total_revenue: number;
        pending_orders: number;
        today_orders: number;
        today_revenue: number;
    };
    recent_orders: Array<{
        id: number;
        order_number: string;
        status: string;
        shipping_status: string;
        total: number;
        created_at: string;
        user: {
            name: string;
            email: string;
        };
        restaurant: {
            name: string;
        };
    }>;
    top_restaurants: Array<{
        id: number;
        name: string;
        orders_count: number;
    }>;
    zyda_orders: {
        data: Array<ZydaOrder>;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    zyda_summary: {
        count: number;
        total_amount: number;
    };
}

interface ZydaOrder {
    id: number;
    name: string | null;
    phone: string;
    address: string | null;
    location: string | null;
    total_amount: string;
    items: Array<Record<string, unknown>> | null;
    created_at: string | null;
    updated_at: string | null;
}

const props = defineProps<Props>();
const page = usePage();

const resolveTabFromUrl = (url: string) => (url.includes('tab=zyda') ? 'zyda' : 'overview');

const activeTab = ref<'overview' | 'zyda'>(resolveTabFromUrl(page.url));

watch(
    () => page.url,
    (newUrl) => {
        activeTab.value = resolveTabFromUrl(newUrl);
    }
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'لوحة التحكم',
        href: '/dashboard',
    },
];

const getStatusColor = (status: string) => {
    const colors = {
        // Original order statuses
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        preparing: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
        ready: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
        delivering: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
        delivered: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        // Shipping statuses
        'New Order': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        'Confirmed': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        'Preparing': 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
        'Ready': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
        'Out for Delivery': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
        'Delivered': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        'Cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    };
    return colors[status as keyof typeof colors] || colors['New Order'];
};

const getStatusText = (status: string) => {
    const statusMap = {
        'New Order': 'طلب جديد',
        'Confirmed': 'مؤكد',
        'Preparing': 'قيد التحضير',
        'Ready': 'جاهز',
        'Out for Delivery': 'خارج للتوصيل',
        'Delivered': 'تم التسليم',
        'Cancelled': 'ملغي',
        pending: 'معلق',
        confirmed: 'مؤكد',
        preparing: 'قيد التحضير',
        ready: 'جاهز',
        delivering: 'قيد التوصيل',
        delivered: 'تم التسليم',
        cancelled: 'ملغي',
    };
    return statusMap[status as keyof typeof statusMap] || status;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return 'الآن';
    if (diffInMinutes < 60) return `منذ ${diffInMinutes} دقيقة`;
    if (diffInMinutes < 1440) return `منذ ${Math.floor(diffInMinutes / 60)} ساعة`;
    return date.toLocaleDateString('ar-SA');
};

const isNewOrder = (order: any) => {
    const orderDate = new Date(order.created_at);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - orderDate.getTime()) / (1000 * 60));
    return diffInMinutes <= 5; // New if created within last 5 minutes
};

// Sound notification for new orders
const playNotificationSound = () => {
    if (!soundEnabled.value) return; // Don't play sound if disabled

    try {
        // Create audio context for notification sound
        const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        // Create a pleasant notification sound
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
        console.log('Audio not supported or blocked by browser');
    }
};

// Check for new orders and play sound
const checkForNewOrders = () => {
    const newOrders = props.recent_orders.filter(order => isNewOrder(order));
    if (newOrders.length > 0) {
        playNotificationSound();
    }
};

// Check for new orders when component mounts
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const lastOrderCount = ref(props.recent_orders.length);
const soundEnabled = ref(true); // Sound is enabled by default

onMounted(() => {
    // Load sound preference from localStorage
    const savedSoundPreference = localStorage.getItem('orderSoundEnabled');
    if (savedSoundPreference !== null) {
        soundEnabled.value = savedSoundPreference === 'true';
    }

    // Check for new orders on mount
    checkForNewOrders();

    // Set up polling for new orders every 30 seconds
    const interval = setInterval(() => {
        router.reload({
            only: ['recent_orders'],
            onSuccess: (page) => {
                // Check if there are new orders
                if (page.props.recent_orders.length > lastOrderCount.value) {
                    playNotificationSound();
                    lastOrderCount.value = page.props.recent_orders.length;
                }
            }
        });
    }, 30000); // 30 seconds

    // Clean up interval when component unmounts
    onUnmounted(() => {
        clearInterval(interval);
    });
});

const zydaOrders = computed(() => props.zyda_orders?.data ?? []);

const formatZydaTotal = (amount: number | string) => {
    const numeric = Number(amount ?? 0);
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 2,
    }).format(numeric);
};

const formatZydaItems = (items: ZydaOrder['items']) => {
    if (!items || items.length === 0) return '—';
    return items
        .map((item: Record<string, any>) => {
            const name = item?.name ?? item?.item_name ?? 'Item';
            const quantity = item?.quantity ?? item?.qty ?? 1;
            return `${name} × ${quantity}`;
        })
        .join('، ');
};

const zydaOrdersCountLabel = computed(() => `${props.zyda_summary?.count ?? 0} طلب`);
const zydaOrdersTotalLabel = computed(() => formatZydaTotal(props.zyda_summary?.total_amount ?? 0));
</script>

<template>
    <Head title="لوحة التحكم" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">لوحة التحكم</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        نظرة سريعة على أداء الطلبات والمطاعم.
                    </p>
                </div>
                <div class="flex items-center gap-2 rounded-full border border-gray-200 bg-white p-1 text-sm font-medium">
                    <button
                        class="rounded-full px-4 py-2 transition"
                        :class="activeTab === 'overview' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'overview'"
                    >
                        تقارير عامة
                    </button>
                    <button
                        class="rounded-full px-4 py-2 transition"
                        :class="activeTab === 'zyda' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'zyda'"
                    >
                        Zyda Orders
                    </button>
                </div>
            </div>

            <div v-if="activeTab === 'overview'" class="space-y-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">المطاعم</p>
                                <p class="text-2xl font-bold">{{ stats.total_restaurants }}</p>
                            </div>
                            <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/20">
                                <Store class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">إجمالي الطلبات</p>
                                <p class="text-2xl font-bold">{{ stats.total_orders }}</p>
                            </div>
                            <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900/20">
                                <ShoppingCart class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">إجمالي الإيرادات</p>
                                <p class="text-2xl font-bold">{{ formatCurrency(stats.total_revenue) }}</p>
                            </div>
                            <div class="rounded-lg bg-emerald-100 p-3 dark:bg-emerald-900/20">
                                <DollarSign class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">الطلبات المعلقة</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_orders }}</p>
                            </div>
                            <div class="rounded-lg bg-yellow-100 p-3 dark:bg-yellow-900/20">
                                <Clock class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">طلبات اليوم</p>
                                <p class="text-2xl font-bold">{{ stats.today_orders }}</p>
                            </div>
                            <div class="rounded-lg bg-indigo-100 p-3 dark:bg-indigo-900/20">
                                <Calendar class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">إيرادات اليوم</p>
                                <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.today_revenue) }}</p>
                            </div>
                            <div class="rounded-lg bg-emerald-100 p-3 dark:bg-emerald-900/20">
                                <TrendingUp class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">طلبات Zyda</p>
                                <p class="text-2xl font-bold text-blue-600">{{ props.zyda_summary.count }}</p>
                            </div>
                            <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                                <Package class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">إجمالي Zyda</p>
                                <p class="text-2xl font-bold text-emerald-600">{{ formatZydaTotal(props.zyda_summary.total_amount) }}</p>
                            </div>
                            <div class="rounded-lg bg-emerald-100 p-3 dark:bg-emerald-900/20">
                                <DollarSign class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Top Restaurants -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Recent Orders -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">الطلبات الأخيرة</h3>
                            <Link
                                :href="route('orders.index')"
                                class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                            >
                                عرض الكل
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="order in recent_orders" :key="order.id" :class="[
                                'group relative flex items-center justify-between rounded-lg border p-4 transition-all duration-200 hover:shadow-md hover:bg-gray-50 dark:hover:bg-gray-800/50',
                                isNewOrder(order) ? 'ring-2 ring-green-400 ring-opacity-50 animate-pulse' : ''
                            ]">
                                <!-- New Order Indicator -->
                                <div v-if="isNewOrder(order)" class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-green-400 to-blue-500"></div>

                                <!-- New Order Badge -->
                                <div v-if="isNewOrder(order)" class="absolute top-2 right-2">
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 animate-bounce">
                                        🆕 جديد
                                    </span>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <div class="rounded-lg bg-gradient-to-br from-blue-100 to-purple-100 p-2 dark:from-blue-900/20 dark:to-purple-900/20">
                                        <Package class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ order.order_number }}</p>
                                        <p class="text-sm text-muted-foreground">{{ order.user.name }} • {{ order.restaurant.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600">{{ formatCurrency(order.total) }}</p>
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium shadow-sm', getStatusColor(order.shipping_status)]">
                                        {{ getStatusText(order.shipping_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Restaurants -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">أفضل المطاعم</h3>
                            <Link
                                :href="route('restaurants.index')"
                                class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                            >
                                عرض الكل
                            </Link>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(restaurant, index) in top_restaurants" :key="restaurant.id" class="flex items-center justify-between rounded-lg border p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ restaurant.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ restaurant.orders_count }} طلب</p>
                                    </div>
                                </div>
                                <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/20">
                                    <Store class="h-4 w-4 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">طلبات Zyda</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                عرض أحدث الطلبات المستوردة من منصة Zyda.
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                إجمالي الطلبات: {{ zydaOrdersCountLabel }}
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                إجمالي القيمة: {{ zydaOrdersTotalLabel }}
                            </div>
                            <Link
                                href="/orders"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                إدارة الطلبات
                            </Link>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-right text-sm">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">الاسم</th>
                                    <th class="px-4 py-3">رقم الهاتف</th>
                                    <th class="px-4 py-3">العنوان</th>
                                    <th class="px-4 py-3">الموقع</th>
                                    <th class="px-4 py-3">الإجمالي</th>
                                    <th class="px-4 py-3">الأصناف</th>
                                    <th class="px-4 py-3">تاريخ الإنشاء</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="zydaOrders.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-muted-foreground">
                                        لا توجد طلبات Zyda مسجلة حتى الآن.
                                    </td>
                                </tr>
                                <tr v-for="order in zydaOrders" :key="order.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ order.name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ order.phone }}</td>
                                    <td class="px-4 py-3 text-gray-700 max-w-xs">{{ order.address ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ order.location ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-900 font-semibold">{{ formatZydaTotal(order.total_amount) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ formatZydaItems(order.items) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ order.created_at ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="props.zyda_orders?.links?.length" class="mt-6 flex justify-center">
                        <nav class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white p-1 text-sm">
                            <Link
                                v-for="link in props.zyda_orders.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="rounded-full px-4 py-2 transition"
                                :class="link.active ? 'bg-gray-900 text-white shadow-sm pointer-events-none' : link.url ? 'text-gray-600 hover:bg-gray-100' : 'text-gray-400 cursor-not-allowed'"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
