<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, ShoppingCart, User, Store, DollarSign, Calendar, Filter } from 'lucide-vue-next';

interface Props {
    orders: Array<{
        id: number;
        order_number: string;
        status: string;
        shipping_status: string;
        total: number;
        items_count?: number;
        items_subtotal?: number;
        sound: boolean;
        created_at: string;
        user: {
            name: string;
            email: string;
        };
        restaurant: {
            name: string;
        };
        order_items?: Array<{
            id: number;
            item_name: string;
            quantity: number;
            price: string;
            subtotal: string;
        }>;
    }>;
}

const props = defineProps<Props>();

// Filter state
const selectedStatus = ref<string>('all');
const filteredOrders = computed(() => {
    if (selectedStatus.value === 'all') {
        return props.orders;
    }
    return props.orders.filter(order => order.shipping_status === selectedStatus.value);
});

// Available status options
const statusOptions = [
    { value: 'all', label: 'جميع الطلبات' },
    { value: 'New Order', label: 'طلب جديد' },
    { value: 'Confirmed', label: 'مؤكد' },
    { value: 'Preparing', label: 'قيد التحضير' },
    { value: 'Ready', label: 'جاهز' },
    { value: 'Out for Delivery', label: 'خارج للتوصيل' },
    { value: 'Delivered', label: 'تم التسليم' },
    { value: 'Cancelled', label: 'ملغي' }
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'لوحة التحكم',
        href: '/dashboard',
    },
    {
        title: 'الطلبات',
        href: '/orders',
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

// Helper function to check if order is delivered
const isDelivered = (order: any) => {
    return order.shipping_status === 'Delivered' ||
           order.shipping_status?.toLowerCase() === 'delivered';
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

    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return date.toLocaleDateString();
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

// Continuous sound for new orders
let continuousSoundInterval: number | null = null;
let audioContext: AudioContext | null = null;
let oscillator: OscillatorNode | null = null;
let gainNode: GainNode | null = null;

// Alternative sound method using HTML5 Audio
const playBeepSound = () => {
    try {
        // Create a simple beep using Web Audio API
        const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.2);

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);

        console.log('Beep sound played');
    } catch (error) {
        console.log('Error playing beep sound:', error);
    }
};

const startContinuousSound = () => {
    if (!soundEnabled.value) {
        console.log('Sound is disabled');
        return;
    }

    if (continuousSoundInterval) {
        console.log('Sound already playing');
        return;
    }

    console.log('Starting continuous sound...');

    try {
        // Create new audio context each time
        audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();

        const playBeep = () => {
            // Use the alternative sound method
            playBeepSound();
        };

        // Play sound every 2 seconds
        continuousSoundInterval = setInterval(playBeep, 2000);
        console.log('Continuous sound started with interval:', continuousSoundInterval);

    } catch (error) {
        console.log('Audio not supported or blocked by browser:', error);
    }
};

const stopContinuousSound = () => {
    console.log('Stopping continuous sound...');

    if (continuousSoundInterval) {
        clearInterval(continuousSoundInterval);
        continuousSoundInterval = null;
        console.log('Cleared sound interval');
    }

    if (oscillator) {
        try {
            oscillator.stop();
            console.log('Stopped oscillator');
        } catch (e) {
            // Oscillator might already be stopped
            console.log('Oscillator already stopped');
        }
        oscillator = null;
    }

    if (audioContext) {
        try {
            audioContext.close();
            console.log('Closed audio context');
        } catch (e) {
            console.log('Audio context already closed');
        }
        audioContext = null;
    }
};

// Check for orders that need sound
const checkForOrdersWithSound = () => {
    const ordersWithSound = filteredOrders.value.filter((order: any) => order.sound === true);
    console.log('Checking for orders with sound enabled:', ordersWithSound.length);

    if (ordersWithSound.length > 0) {
        console.log('Found orders with sound enabled, starting sound...');
        // Start continuous sound for orders with sound enabled (only if not already playing)
        if (!continuousSoundInterval) {
            startContinuousSound();
        }
    } else {
        console.log('No orders with sound enabled, stopping sound...');
        // Stop sound if no orders need it
        stopContinuousSound();
    }
};

// Monitor for orders with sound continuously
const monitorOrdersWithSound = () => {
    console.log('Starting sound monitoring...');

    // Check immediately
    checkForOrdersWithSound();

    // Set up continuous monitoring every 5 seconds
    const monitorInterval = setInterval(() => {
        console.log('Monitoring orders for sound...');
        checkForOrdersWithSound();
    }, 5000);

    // Return cleanup function
    return () => {
        clearInterval(monitorInterval);
        console.log('Stopped sound monitoring');
    };
};

// Check for new orders when component mounts
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const lastOrderCount = ref(props.orders.length);
const soundEnabled = ref(true); // Sound is enabled by default

// Toggle sound notifications
const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value;
    // Save preference to localStorage
    localStorage.setItem('orderSoundEnabled', soundEnabled.value.toString());
};

// Accept order function
const acceptOrder = async (orderId: number) => {
    // Stop sound immediately when Accept is clicked
    stopContinuousSound();

    try {
        await router.patch(route('orders.accept', orderId), {}, {
            onSuccess: () => {
                // Reload the page to show updated status
                router.reload({ only: ['orders'] });
            },
            onError: (errors) => {
                console.error('Error accepting order:', errors);
            }
        });
    } catch (error) {
        console.error('Error accepting order:', error);
    }
};

// Load sound preference and set up notifications
onMounted(() => {
    // Load sound preference from localStorage
    const savedSoundPreference = localStorage.getItem('orderSoundEnabled');
    if (savedSoundPreference !== null) {
        soundEnabled.value = savedSoundPreference === 'true';
    }

    // Start monitoring for orders with sound
    const stopMonitoring = monitorOrdersWithSound();

    // Set up polling for new orders every 10 seconds (faster monitoring)
    const interval = setInterval(() => {
        console.log('Reloading orders...');
        router.reload({
            only: ['orders'],
            onSuccess: (page: any) => {
                console.log('Orders reloaded, checking for sound...');
                // Check for orders with sound enabled
                checkForOrdersWithSound();
                    lastOrderCount.value = page.props.orders.length;
            }
        });
    }, 10000); // 10 seconds for faster detection

    // Clean up interval when component unmounts
    onUnmounted(() => {
        clearInterval(interval);
        stopMonitoring(); // Stop monitoring
        stopContinuousSound(); // Stop any playing sounds
    });
});
</script>

<template>
    <Head title="الطلبات" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div>
                        <h1 class="text-2xl font-bold">الطلبات</h1>
                        <p class="text-muted-foreground">إدارة جميع طلبات العملاء</p>
                        <div v-if="filteredOrders.filter((order: any) => order.sound === true).length > 0" class="mt-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                🔔 {{ filteredOrders.filter((order: any) => order.sound === true).length }} طلب بصوت نشط
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Sound Toggle -->
                    <div class="flex items-center space-x-2">
                        <button
                            @click="toggleSound"
                            :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                soundEnabled ? 'bg-green-600' : 'bg-gray-200 dark:bg-gray-700'
                            ]"
                        >
                            <span
                                :class="[
                                    'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                    soundEnabled ? 'translate-x-6' : 'translate-x-1'
                                ]"
                            />
                        </button>
                        <span class="text-sm text-muted-foreground">
                            {{ soundEnabled ? '🔊 الصوت مفعل' : '🔇 الصوت معطل' }}
                        </span>
                        <button
                            @click="playBeepSound"
                            class="ml-2 px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            اختبار الصوت
                        </button>
                    </div>

                    <!-- Create Order Button -->
                    <Link
                        :href="route('orders.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        <Plus class="h-4 w-4" />
                        إنشاء طلب
                    </Link>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="flex items-center justify-between bg-white p-4 rounded-lg border shadow-sm">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-5 w-5 text-gray-500" />
                        <span class="text-sm font-medium text-gray-700">فلتر الطلبات:</span>
                    </div>
                    <select
                        v-model="selectedStatus"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                <div class="text-sm text-gray-500">
                    عرض {{ filteredOrders.length }} من {{ props.orders.length }} طلب
                </div>
            </div>

            <!-- Orders List -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="order in filteredOrders" :key="order.id" :class="[
                    'group relative overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-200',
                    isNewOrder(order) ? 'ring-2 ring-green-400 ring-opacity-50 animate-pulse hover:shadow-lg hover:scale-[1.02]' : '',
                    isDelivered(order) ? 'bg-gray-50 border-gray-200 opacity-30 grayscale-[0.6] cursor-not-allowed pointer-events-none' : 'hover:shadow-lg hover:scale-[1.02]'
                ]">
                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4 z-10">
                        <span :class="['inline-flex rounded-full px-3 py-1 text-xs font-medium shadow-sm', getStatusColor(order.shipping_status)]">
                            {{ order.shipping_status }}
                        </span>
                    </div>

                    <!-- New Order Indicator -->
                    <div v-if="isNewOrder(order)" class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-green-400 to-blue-500 z-10"></div>

                    <!-- New Order Badge -->
                    <div v-if="isNewOrder(order)" class="absolute top-4 left-4 z-10">
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 animate-bounce">
                            🆕 NEW
                        </span>
                    </div>

                    <!-- Order Header -->
                    <div class="p-6 pb-4">
                        <div class="flex items-center space-x-3 mb-4">
                            <div :class="[
                                'rounded-lg p-3',
                                isDelivered(order)
                                    ? 'bg-gray-300 dark:bg-gray-600'
                                    : 'bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900/20 dark:to-blue-900/20'
                            ]">
                                <ShoppingCart :class="[
                                    'h-6 w-6',
                                    isDelivered(order)
                                        ? 'text-gray-400 dark:text-gray-500'
                                        : 'text-purple-600 dark:text-purple-400'
                                ]" />
                            </div>
                            <div class="flex-1">
                                <h3 :class="[
                                    'font-bold text-lg',
                                    isDelivered(order)
                                        ? 'text-gray-500 dark:text-gray-500'
                                        : 'text-foreground'
                                ]">{{ order.order_number }}</h3>
                                <p class="text-sm text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="space-y-3">
                            <!-- Customer Info -->
                            <div class="flex items-center space-x-2 text-sm">
                                <User class="h-4 w-4 text-gray-500" />
                                <span :class="[
                                    'font-medium',
                                    isDelivered(order)
                                        ? 'text-gray-500'
                                        : 'text-gray-900'
                                ]">{{ order.user.name }}</span>
                            </div>

                            <!-- Restaurant Info -->
                            <div class="flex items-center space-x-2 text-sm">
                                <Store class="h-4 w-4 text-gray-500" />
                                <span :class="[
                                    'font-medium',
                                    isDelivered(order)
                                        ? 'text-gray-500'
                                        : 'text-gray-900'
                                ]">{{ order.restaurant.name }}</span>
                            </div>

                            <!-- Items Count -->
                            <div class="flex items-center space-x-2 text-sm">
                                <ShoppingCart class="h-4 w-4 text-gray-500" />
                                <span :class="[
                                    isDelivered(order)
                                        ? 'text-gray-500'
                                        : 'text-gray-900'
                                ]">{{ order.items_count || 0 }} items</span>
                                <span v-if="order.items_subtotal" :class="[
                                    'text-muted-foreground',
                                    isDelivered(order)
                                        ? 'text-gray-400'
                                        : 'text-muted-foreground'
                                ]">
                                    ({{ formatCurrency(order.items_subtotal) }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Footer -->
                    <div :class="[
                        'px-6 py-4 border-t',
                        isDelivered(order)
                            ? 'bg-gray-100 dark:bg-gray-700/50'
                            : 'bg-gray-50 dark:bg-gray-800/50'
                    ]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span :class="[
                                    'text-xl font-bold',
                                    isDelivered(order)
                                        ? 'text-gray-500'
                                        : 'text-green-600'
                                ]">{{ formatCurrency(order.total) }}</span>
                            </div>
                            <div v-if="!isDelivered(order)" class="flex space-x-2">
                                <button
                                    v-if="order.shipping_status === 'New Order'"
                                    @click="acceptOrder(order.id)"
                                    class="rounded-lg px-4 py-2 text-xs font-medium transition-colors bg-green-600 text-white hover:bg-green-700"
                                >
                                    قبول
                                </button>
                                <Link
                                    :href="route('orders.show', order.id)"
                                    class="rounded-lg px-4 py-2 text-xs font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700"
                                >
                                    عرض
                                </Link>
                                <Link
                                    :href="route('orders.edit', order.id)"
                                    class="rounded-lg border px-4 py-2 text-xs font-medium transition-colors border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    تعديل
                                </Link>
                            </div>
                            <div v-else class="flex space-x-2">
                                <span class="rounded-lg px-4 py-2 text-xs font-medium bg-gray-300 text-gray-500 cursor-not-allowed">
                                    عرض
                                </span>
                                <span class="rounded-lg border px-4 py-2 text-xs font-medium border-gray-300 bg-gray-200 text-gray-400 cursor-not-allowed">
                                    تعديل
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-12">
                <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">No orders found</h3>
                <p class="mt-2 text-muted-foreground">Orders will appear here once customers start placing them.</p>
                <Link
                    :href="route('orders.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إنشاء طلب
                </Link>
            </div>

            <!-- Empty State for Filtered Results -->
            <div v-if="filteredOrders.length === 0 && props.orders.length > 0" class="flex flex-col items-center justify-center py-12">
                <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">لا توجد طلبات بهذه الحالة</h3>
                <p class="mt-2 text-muted-foreground">جرب اختيار حالة أخرى من الفلتر.</p>
            </div>
        </div>
    </AppLayout>
</template>
