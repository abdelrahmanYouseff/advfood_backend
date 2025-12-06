<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, ShoppingCart, User, Store, DollarSign, Calendar, Filter, AlertCircle, CheckCircle2, Timer, Truck, UserCircle2, Link2 } from 'lucide-vue-next';

interface OrderUser {
    name: string;
    email: string;
}

interface OrderRestaurant {
    name: string;
}

interface OrderData {
    id: number;
    order_number: string;
    status: string;
    shipping_status: string | null;
    total: number;
    source?: string | null;
    items_count?: number;
    items_subtotal?: number;
    sound: boolean;
    created_at: string;
    // Zeada (Zyda) scraped order info – to show unique Zyda code if available
    zyda_order?: {
        zyda_order_key?: string | null;
    } | null;
    delivered_at?: string | null;
    dsp_order_id?: string | null;
    driver_name?: string | null;
    driver_phone?: string | null;
    user: OrderUser;
    restaurant: OrderRestaurant;
        order_items?: Array<{
            id: number;
            item_name: string;
            quantity: number;
            price: string;
            subtotal: string;
        }>;
}

interface DriverInfo {
    status?: string | null;
    driver_name?: string | null;
    driver_phone?: string | null;
}

interface Props {
    orders: Array<OrderData>;
    closed_orders?: Array<OrderData>;
    statistics?: {
        total_new_orders: number;
        total_closed_orders: number;
    };
}

const props = defineProps<Props>();

const page = usePage();

// Orders page language (synced with sidebarLang)
const ordersLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        ordersLang.value = storedLang;
    }
}

onMounted(() => {
    if (typeof window === 'undefined') return;

    const handler = (event: Event) => {
        const lang = (event as CustomEvent).detail;
        if (lang === 'en' || lang === 'ar') {
            ordersLang.value = lang;
        }
    };

    window.addEventListener('sidebar-lang-changed', handler as EventListener);

    onUnmounted(() => {
        window.removeEventListener('sidebar-lang-changed', handler as EventListener);
    });
});

const t = (ar: string, en: string) => (ordersLang.value === 'ar' ? ar : en);

// Filter state
const selectedStatus = ref<string>('all');
const viewMode = ref<'cards' | 'table'>('cards'); // 'cards' or 'table' view for orders list
const filteredOrders = computed(() => {
    if (selectedStatus.value === 'all') {
        return props.orders;
    }
    return props.orders.filter(order => order.shipping_status === selectedStatus.value);
});

// Closed orders (delivered / cancelled)
const closedOrders = computed<OrderData[]>(() => props.closed_orders ?? []);

// Available status options
const statusOptions = [
    { value: 'all', label: t('جميع الطلبات', 'All orders') },
    { value: 'New Order', label: t('طلب جديد', 'New order') },
    { value: 'Confirmed', label: t('مؤكد', 'Confirmed') },
    { value: 'Preparing', label: t('قيد التحضير', 'Preparing') },
    { value: 'Ready', label: t('جاهز', 'Ready') },
    { value: 'Out for Delivery', label: t('خارج للتوصيل', 'Out for delivery') },
    { value: 'Delivered', label: t('تم التسليم', 'Delivered') },
    { value: 'Cancelled', label: t('ملغي', 'Cancelled') }
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('لوحة التحكم', 'Dashboard'),
        href: '/dashboard',
    },
    {
        title: t('الطلبات', 'Orders'),
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

const orderStatusLabels: Record<string, string> = {
    pending: 'قيد الانتظار',
    confirmed: 'تم التأكيد',
    preparing: 'قيد التحضير',
    ready: 'جاهز للاستلام',
    delivering: 'جاري التوصيل',
    delivered: 'تم التسليم',
    cancelled: 'تم الإلغاء',
};

const getOrderStatusLabel = (status?: string | null) => {
    if (!status) {
        return 'غير محدد';
    }
    const normalizedStatus = status.toLowerCase();
    return orderStatusLabels[normalizedStatus] ?? status;
};

const shippingStatusLabels: Record<string, string> = {
    'new order': 'طلب جديد',
    'confirmed': 'تم التأكيد',
    'order accept': 'تم قبول الطلب',
    'preparing': 'قيد التحضير',
    'ready': 'جاهز',
    'out for delivery': 'جاري التوصيل',
    'delivered': 'تم التسليم',
    'cancelled': 'ملغي',
};

const getShippingStatusLabel = (status?: string | null) => {
    if (!status) {
        return 'غير محدد';
    }
    const normalizedStatus = status.toLowerCase();
    return shippingStatusLabels[normalizedStatus] ?? status;
};

// Live driver info and driver/shipping status (fetched from shipping API)
const driverInfoByOrderId = ref<Record<number, DriverInfo>>({});

const getDriverStatusForOrder = (order: OrderData): string => {
    const info = driverInfoByOrderId.value[order.id];
    return (info?.status ?? order.shipping_status ?? '') || '';
};

const getDriverNameForOrder = (order: OrderData): string | null => {
    const info = driverInfoByOrderId.value[order.id];
    return info?.driver_name ?? order.driver_name ?? null;
};

const getDriverPhoneForOrder = (order: OrderData): string | null => {
    const info = driverInfoByOrderId.value[order.id];
    return info?.driver_phone ?? order.driver_phone ?? null;
};

const sourceLabels: Record<string, string> = {
    link: 'Link',
    application: 'Application',
    internal: 'Internal',
    web: 'Web',
};

const getSourceLabel = (source?: string | null) => {
    if (!source) {
        return '—';
    }
    const normalizedSource = source.toLowerCase();
    return sourceLabels[normalizedSource] ?? source;
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

const currentTimestamp = ref(Date.now());
let elapsedTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    elapsedTimer = setInterval(() => {
        currentTimestamp.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (elapsedTimer !== null) {
        clearInterval(elapsedTimer);
        elapsedTimer = null;
    }
    if (driverStatusInterval !== null) {
        clearInterval(driverStatusInterval);
        driverStatusInterval = null;
    }
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)}h ago`;
    return date.toLocaleDateString();
};

const padTime = (value: number) => value.toString().padStart(2, '0');

const formatElapsedTime = (order: any) => {
    if (!order?.created_at) {
        return '00:00';
    }
    const createdAt = new Date(order.created_at).getTime();
    if (Number.isNaN(createdAt)) {
        return '00:00';
    }
    let diff = Math.max(0, Math.floor((currentTimestamp.value - createdAt) / 1000));
    const hours = Math.floor(diff / 3600);
    diff %= 3600;
    const minutes = Math.floor(diff / 60);
    const seconds = diff % 60;

    if (hours > 0) {
        return `${padTime(hours)}:${padTime(minutes)}:${padTime(seconds)}`;
    }
    return `${padTime(minutes)}:${padTime(seconds)}`;
};

const isNewOrder = (order: any) => {
    const orderDate = new Date(order.created_at);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - orderDate.getTime()) / (1000 * 60));
    return diffInMinutes <= 5; // New if created within last 5 minutes
};

// Poll shipping API for each order that has dsp_order_id to keep driver info and status up to date
const fetchDriverStatusForOrder = async (order: OrderData) => {
    if (!order.dsp_order_id) {
        return;
    }

    try {
        const response = await fetch(`/api/shipping/status/${encodeURIComponent(order.dsp_order_id)}`);
        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        const api = payload.api ?? {};
        const local = payload.local ?? {};

        const apiStatus = api.status ?? api.data?.status ?? null;
        const localStatus = local.shipping_status ?? null;
        const status = apiStatus ?? localStatus ?? order.shipping_status ?? null;

        const apiDriver = api.driver ?? api.data?.driver ?? null;
        const driverName =
            (apiDriver && typeof apiDriver === 'object' ? apiDriver.name : null) ??
            local.driver_name ??
            order.driver_name ??
            null;
        const driverPhone =
            (apiDriver && typeof apiDriver === 'object' ? apiDriver.phone : null) ??
            local.driver_phone ??
            order.driver_phone ??
            null;

        driverInfoByOrderId.value[order.id] = {
            status,
            driver_name: driverName,
            driver_phone: driverPhone,
        };

        // Also keep the order object in sync so existing UI bindings update
        if (status) {
            order.shipping_status = status;
        }
        if (driverName) {
            order.driver_name = driverName;
        }
        if (driverPhone) {
            order.driver_phone = driverPhone;
        }
    } catch (error) {
        console.error('Failed to fetch driver status for order', order.id, error);
    }
};

let driverStatusInterval: ReturnType<typeof setInterval> | null = null;

const startDriverStatusPolling = () => {
    const poll = () => {
        // Only poll for open orders that have been sent to shipping (have dsp_order_id)
        filteredOrders.value.forEach((order: OrderData) => {
            if (order.dsp_order_id && !isDelivered(order)) {
                fetchDriverStatusForOrder(order);
            }
        });
    };

    // Initial fetch
    poll();

    // Poll every 15 seconds
    driverStatusInterval = setInterval(poll, 15000);
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

// Track announced orders and their daily numbers
const announcedOrders = ref<Map<number, number>>(new Map()); // orderId -> dailyNumber
const lastCheckedDate = ref<string>('');
let announcementIntervals = new Map<number, number>(); // orderId -> intervalId

// Calculate daily order number (starts from 1 each day)
const getDailyOrderNumber = (order: any): number => {
    const today = new Date().toDateString();
    const orderDate = new Date(order.created_at).toDateString();

    // Reset if it's a new day
    if (lastCheckedDate.value !== today) {
        announcedOrders.value.clear();
        lastCheckedDate.value = today;
    }

    // If already assigned, return it
    if (announcedOrders.value.has(order.id)) {
        return announcedOrders.value.get(order.id)!;
    }

    // Calculate number based on orders from today (sorted by creation time)
    const todayOrders = props.orders
        .filter(o => new Date(o.created_at).toDateString() === today)
        .sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());

    const orderIndex = todayOrders.findIndex(o => o.id === order.id);
    const dailyNumber = orderIndex + 1;

    // Store it
    announcedOrders.value.set(order.id, dailyNumber);

    return dailyNumber;
};

// Check if order is not accepted (needs announcement)
const isUnacceptedOrder = (order: any): boolean => {
    return order.shipping_status === 'New Order' ||
           order.status === 'pending' ||
           order.shipping_status?.toLowerCase() === 'new order';
};

// Play female voice announcement for an order
const playOrderAnnouncement = (order: any) => {
    if (!('speechSynthesis' in window) || !soundEnabled.value) {
        return;
    }

    const dailyNumber = getDailyOrderNumber(order);
    const message = `New Order Number ${dailyNumber}`;

    // Cancel any pending speech
    if (speechSynthesis.speaking || speechSynthesis.pending) {
        speechSynthesis.cancel();
    }

    // Get voices
    let voices = speechSynthesis.getVoices();

    const speak = () => {
        if (voices.length === 0) {
            voices = speechSynthesis.getVoices();
            if (voices.length === 0) {
                setTimeout(speak, 100);
                return;
            }
        }

        const utterance = new SpeechSynthesisUtterance(message);
        utterance.lang = 'en-US';
        utterance.rate = 0.9;
        utterance.pitch = 1.2;
        utterance.volume = 1;

        // Find female voice
        const femaleVoice = voices.find(v =>
            v.name.toLowerCase().includes('samantha') ||
            v.name.toLowerCase().includes('zira') ||
            v.name.toLowerCase().includes('hazel') ||
            v.name.toLowerCase().includes('karen') ||
            v.name.toLowerCase().includes('susan') ||
            (v.name.toLowerCase().includes('female') && v.lang.includes('en'))
        ) || voices.find(v => v.lang.includes('en-US')) || voices.find(v => v.lang.includes('en')) || voices[0];

        if (femaleVoice) {
            utterance.voice = femaleVoice;
        }

        utterance.onerror = (e: any) => {
            if (e.error !== 'canceled' && e.error !== 'interrupted') {
                console.error('Speech error for order', order.id, ':', e.error);
            }
        };

        try {
            speechSynthesis.speak(utterance);
            console.log(`🔊 Announced: ${message} for order ${order.id}`);
        } catch (error) {
            console.error('Error speaking:', error);
        }
    };

    // Set up voices if needed
    if (voices.length === 0) {
        speechSynthesis.onvoiceschanged = () => {
            voices = speechSynthesis.getVoices();
            speak();
        };
    } else {
        speak();
    }
};

// Start repeating announcement for an order
const startOrderAnnouncement = (order: any) => {
    // Stop if already announcing
    if (announcementIntervals.has(order.id)) {
        return;
    }

    // Play immediately
    playOrderAnnouncement(order);

    // Repeat every 5 seconds until order is accepted
    const intervalId = setInterval(() => {
        // Check if order still needs announcement
        const currentOrder = props.orders.find(o => o.id === order.id);
        if (!currentOrder || !isUnacceptedOrder(currentOrder)) {
            stopOrderAnnouncement(order.id);
            return;
        }
        playOrderAnnouncement(currentOrder);
    }, 5000);

    announcementIntervals.set(order.id, intervalId);
    console.log(`🔔 Started announcement for order ${order.id}`);
};

// Stop announcement for an order
const stopOrderAnnouncement = (orderId: number) => {
    const intervalId = announcementIntervals.get(orderId);
    if (intervalId) {
        clearInterval(intervalId);
        announcementIntervals.delete(orderId);
        console.log(`🔕 Stopped announcement for order ${orderId}`);
    }
    // Cancel any ongoing speech
    if (speechSynthesis.speaking) {
        speechSynthesis.cancel();
    }
};

// Track which new orders we've already notified about
const notifiedNewOrders = ref<Set<number>>(new Set());

// Check for orders that need sound announcements
const checkForOrdersWithSound = () => {
    // Get all new orders (created within last 5 minutes) that are still unaccepted
    const newOrders = filteredOrders.value.filter(
        (order: any) => isNewOrder(order) && isUnacceptedOrder(order)
    );

    // Get unaccepted orders (New Order status)
    const unacceptedOrders = filteredOrders.value.filter((order: any) => isUnacceptedOrder(order));

    // Combine both: new orders OR unaccepted orders (unique by ID)
    const ordersNeedingSound: any[] = Array.from(
        new Map<number, any>([
            ...newOrders.map(order => [order.id, order] as [number, any]),
            ...unacceptedOrders.map(order => [order.id, order] as [number, any]),
        ]).values()
    );

    console.log('Checking for orders needing sound:', {
        newOrders: newOrders.length,
        unacceptedOrders: unacceptedOrders.length,
        total: ordersNeedingSound.length
    });

    // Play sound for newly detected orders (not notified yet)
    newOrders.forEach((order: any) => {
        if (!notifiedNewOrders.value.has(order.id)) {
            console.log(`🔔 New order detected: ${order.id} - Playing notification sound`);
            playNotificationSound();
            notifiedNewOrders.value.add(order.id);
        }
    });

    if (ordersNeedingSound.length === 0) {
        // Stop all announcements if no orders need sound
        announcementIntervals.forEach((intervalId, orderId) => {
            stopOrderAnnouncement(orderId);
        });
        stopContinuousSound();
        // Clean up old notified orders (older than 5 minutes)
        const fiveMinutesAgo = Date.now() - (5 * 60 * 1000);
        filteredOrders.value.forEach((order: any) => {
            const orderTime = new Date(order.created_at).getTime();
            if (orderTime < fiveMinutesAgo) {
                notifiedNewOrders.value.delete(order.id);
            }
        });
        return;
    }

    // Sort orders by creation date (newest first)
    const sortedOrders = [...ordersNeedingSound].sort((a: any, b: any) => {
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });

    // Only announce the newest order
    const newestOrder: any = sortedOrders[0];

    // Stop announcements for all other orders (older ones)
    announcementIntervals.forEach((intervalId, orderId) => {
        if (orderId !== newestOrder.id) {
            stopOrderAnnouncement(orderId);
        }
    });

    // Start announcement only for the newest order if not already announcing
    if (!announcementIntervals.has(newestOrder.id)) {
        console.log(`🔔 Starting announcement for newest order: ${newestOrder.id} (daily number: ${getDailyOrderNumber(newestOrder)})`);
        startOrderAnnouncement(newestOrder);
    }

    // Legacy continuous sound - keep for backward compatibility but prioritize voice announcements
    const ordersWithSound = filteredOrders.value.filter((order: any) => order.sound === true);
    if (ordersWithSound.length === 0) {
        stopContinuousSound();
    }
};

// Monitor for orders with sound continuously
const monitorOrdersWithSound = () => {
    console.log('Starting sound monitoring...');

    // Check immediately
    checkForOrdersWithSound();

    // Set up continuous monitoring every 3 seconds (matching order polling)
    const monitorInterval = setInterval(() => {
        console.log('Monitoring orders for sound...');
        checkForOrdersWithSound();
    }, 3000);

    // Return cleanup function
    return () => {
        clearInterval(monitorInterval);
        console.log('Stopped sound monitoring');
    };
};

// Check for new orders when component mounts
import { onMounted, onUnmounted, ref, computed } from 'vue';

const lastOrderCount = ref(props.orders.length);
const soundEnabled = ref(true); // Sound is enabled by default

// Toggle sound notifications
const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value;
    // Save preference to localStorage
    localStorage.setItem('orderSoundEnabled', soundEnabled.value.toString());
};

// Test announcement function - plays "New Order Number 1" once
const testAnnouncement = () => {
    if (!('speechSynthesis' in window)) {
        alert('❌ Speech synthesis غير مدعوم في هذا المتصفح');
        return;
    }

    console.log('🧪 Test announcement button clicked');

    // For Chrome: Cancel any pending speech first, then wait a bit
    if (speechSynthesis.speaking || speechSynthesis.pending) {
        console.log('🛑 Canceling existing speech...');
        speechSynthesis.cancel();
        // Chrome needs a bit more time
        setTimeout(() => {
            doSpeak();
        }, 200);
    } else {
        // Direct call for immediate execution
        doSpeak();
    }
};

// Direct speech function
const doSpeak = () => {
    // Get voices (Chrome loads them asynchronously)
    let voices = speechSynthesis.getVoices();

    // Chrome often needs to wait for voices
    if (voices.length === 0) {
        console.log('⏳ Loading voices (Chrome)...');

        // Try multiple methods for Chrome compatibility
        const loadVoices = () => {
            voices = speechSynthesis.getVoices();
            if (voices.length > 0) {
                console.log(`✅ Loaded ${voices.length} voices`);
                // Small delay for Chrome
                setTimeout(() => speakNow(voices), 50);
            } else {
                // Retry with exponential backoff
                setTimeout(loadVoices, 100);
            }
        };

        // Set up voices changed listener (important for Chrome)
        const voicesChanged = () => {
            voices = speechSynthesis.getVoices();
            if (voices.length > 0) {
                console.log(`✅ Voices loaded via event: ${voices.length}`);
                speakNow(voices);
            }
        };

        speechSynthesis.onvoiceschanged = voicesChanged;

        // Also try loading immediately
        loadVoices();
        return;
    }

    // Chrome: small delay to ensure voices are ready
    setTimeout(() => speakNow(voices), 10);
};

// Actually speak the message
const speakNow = (voices: SpeechSynthesisVoice[]) => {
    // Cancel any remaining speech (Chrome can queue)
    if (speechSynthesis.speaking || speechSynthesis.pending) {
        speechSynthesis.cancel();
        setTimeout(() => createAndPlayUtterance(voices), 100);
    } else {
        createAndPlayUtterance(voices);
    }
};

// Create and play utterance (separated for Chrome compatibility)
const createAndPlayUtterance = (voices: SpeechSynthesisVoice[]) => {
    // Create utterance
    const utterance = new SpeechSynthesisUtterance('New Order Number 1');
    utterance.lang = 'en-US';
    utterance.rate = 0.9;
    utterance.pitch = 1.2;
    utterance.volume = 1;

    // Find female voice
    const femaleVoice = voices.find(v =>
        v.name.toLowerCase().includes('samantha') ||
        v.name.toLowerCase().includes('zira') ||
        v.name.toLowerCase().includes('hazel') ||
        v.name.toLowerCase().includes('karen') ||
        v.name.toLowerCase().includes('susan') ||
        (v.name.toLowerCase().includes('female') && v.lang.includes('en'))
    ) || voices.find(v => v.lang.includes('en-US')) || voices.find(v => v.lang.includes('en')) || voices[0];

    if (femaleVoice) {
        utterance.voice = femaleVoice;
        console.log(`🎤 Using voice: ${femaleVoice.name} (${femaleVoice.lang})`);
    } else {
        console.log('⚠️ No voice selected, using default');
    }

    let started = false;
    let ended = false;

    utterance.onstart = () => {
        started = true;
        ended = false;
        console.log('✅ ✅ ✅ الصوت بدأ! يجب أن تسمع الصوت الآن!');
    };

    utterance.onend = () => {
        ended = true;
        console.log('✅ ✅ ✅ انتهى الصوت بنجاح!');
    };

    utterance.onerror = (e: any) => {
        console.error('❌ خطأ في الصوت:', e.error, e);
        // Chrome sometimes fires 'canceled' error even when it works
        if (e.error !== 'canceled' && e.error !== 'interrupted') {
            alert(`❌ خطأ في الصوت: ${e.error}`);
        }
    };

    console.log('🔊 🔊 🔊 محاولة تشغيل الصوت الآن (Chrome/Safari compatible)...');

    // Try to speak (with error handling)
    try {
        // Chrome: Make sure we're not queuing
        if (speechSynthesis.pending) {
            console.log('⚠️ Clearing pending speech for Chrome...');
            speechSynthesis.cancel();
            setTimeout(() => {
                speechSynthesis.speak(utterance);
                console.log('✅ speechSynthesis.speak() تم استدعاؤه (بعد cancel)');
            }, 50);
        } else {
            speechSynthesis.speak(utterance);
            console.log('✅ speechSynthesis.speak() تم استدعاؤه');
        }

        // Check status after delay (longer for Chrome)
        setTimeout(() => {
            console.log('📊 حالة الصوت:', {
                speaking: speechSynthesis.speaking,
                pending: speechSynthesis.pending,
                started: started,
                ended: ended
            });

            if (!started && !speechSynthesis.speaking && !speechSynthesis.pending) {
                console.error('❌ الصوت لم يبدأ!');
                // Don't show alert if it's Chrome - it might still be processing
            }
        }, 1500);
    } catch (error: any) {
        console.error('❌ خطأ في speak():', error);
        alert('❌ خطأ: ' + (error.message || error));
    }
};

// Accept order function
const acceptOrder = async (orderId: number) => {
    // Stop announcement immediately when Accept is clicked
    stopOrderAnnouncement(orderId);
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

// Create test order function
// Load sound preference and set up notifications
onMounted(() => {
    // Load sound preference from localStorage
    const savedSoundPreference = localStorage.getItem('orderSoundEnabled');
    if (savedSoundPreference !== null) {
        soundEnabled.value = savedSoundPreference === 'true';
    }

    // Enable speech after first user interaction (required by browsers)
    const enableSpeechOnInteraction = () => {
        console.log('✅ User interaction detected - speech enabled');
        // Test speech is now enabled by creating a silent utterance
        const testUtterance = new SpeechSynthesisUtterance('');
        testUtterance.volume = 0;
        speechSynthesis.speak(testUtterance);
        speechSynthesis.cancel(); // Cancel immediately
    };

    // Add click listener to enable speech (only once)
    document.addEventListener('click', enableSpeechOnInteraction, { once: true });
    document.addEventListener('touchstart', enableSpeechOnInteraction, { once: true });

    // Check for new orders on initial load
    checkForOrdersWithSound();

    // Mark existing new orders as notified to avoid duplicate sounds
    filteredOrders.value.forEach((order: any) => {
        if (isNewOrder(order)) {
            notifiedNewOrders.value.add(order.id);
        }
    });

    // Start monitoring for orders with sound
    const stopMonitoring = monitorOrdersWithSound();

    // Start polling driver / shipping status from shipping company
    startDriverStatusPolling();

    // Set up polling for new orders every 3 seconds (real-time monitoring)
    const interval = setInterval(() => {
        console.log('🔄 Polling for new orders...');
        const previousOrderIds = new Set(filteredOrders.value.map((o: any) => o.id));
        router.reload({
            only: ['orders'],
            // @ts-expect-error preserveScroll exists at runtime but is missing from TS types
            preserveScroll: true,
            onSuccess: (page: any) => {
                console.log('✅ Orders reloaded, checking for new orders and sound...');
                const currentOrderIds = new Set(page.props.orders.map((o: any) => o.id));

                // Check for new orders (orders that weren't in previous list)
                const newlyAddedOrders = page.props.orders.filter((order: any) => !previousOrderIds.has(order.id));
                if (newlyAddedOrders.length > 0) {
                    console.log(`🆕 Found ${newlyAddedOrders.length} new order(s) - Playing sound immediately!`);
                    // Play sound IMMEDIATELY for ALL new orders (before checking status)
                    newlyAddedOrders.forEach((order: any) => {
                        if (!notifiedNewOrders.value.has(order.id)) {
                            console.log(`🔔 Playing sound immediately for new order: ${order.id} (${order.order_number})`);
                            playNotificationSound();
                            notifiedNewOrders.value.add(order.id);
                        }
                    });
                    // Also check for orders with sound enabled
                    checkForOrdersWithSound();
                } else {
                    // Check for orders with sound enabled (even if no new orders)
                    checkForOrdersWithSound();
                }

                lastOrderCount.value = page.props.orders.length;
            }
        });
    }, 3000); // 3 seconds for real-time detection

    // Clean up interval when component unmounts
    onUnmounted(() => {
        clearInterval(interval);
        stopMonitoring(); // Stop monitoring
        stopContinuousSound(); // Stop any playing sounds

        // Stop all order announcements
        announcementIntervals.forEach((intervalId) => {
            clearInterval(intervalId);
        });
        announcementIntervals.clear();

        // Cancel any ongoing speech
        if (speechSynthesis.speaking || speechSynthesis.pending) {
            speechSynthesis.cancel();
        }

        document.removeEventListener('click', enableSpeechOnInteraction);
        document.removeEventListener('touchstart', enableSpeechOnInteraction);

        if (driverStatusInterval !== null) {
            clearInterval(driverStatusInterval);
            driverStatusInterval = null;
        }
    });
});
</script>

<template>
    <Head :title="t('الطلبات', 'Orders')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ t('الطلبات', 'Orders') }}
                        </h1>
                        <p class="text-muted-foreground">
                            {{ t('إدارة جميع طلبات العملاء', 'Manage all customer orders') }}
                        </p>
                        <div v-if="filteredOrders.filter((order: any) => order.sound === true).length > 0" class="mt-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 animate-pulse">
                                🔔
                                <span v-if="ordersLang === 'ar'">
                                    {{ filteredOrders.filter((order: any) => order.sound === true).length }} طلب بصوت نشط
                                </span>
                                <span v-else>
                                    {{ filteredOrders.filter((order: any) => order.sound === true).length }} orders with active sound
                                </span>
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
                        <button
                            @click="testAnnouncement"
                            class="ml-2 px-2 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700"
                        >
                            اختبار الإعلان الصوتي
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('orders.create')"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition"
                        >
                            <Plus class="h-4 w-4" />
                            {{ t('إنشاء طلب', 'Create order') }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Total New Orders Card -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">الطلبات الجديدة</p>
                            <p class="text-3xl font-bold text-yellow-600">{{ props.statistics?.total_new_orders || 0 }}</p>
                            <p class="text-xs text-muted-foreground mt-1">طلبات لم يتم قبولها بعد</p>
                        </div>
                        <div class="rounded-lg bg-yellow-100 p-3 dark:bg-yellow-900/20">
                            <AlertCircle class="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
                        </div>
                    </div>
                </div>

                <!-- Total Closed Orders Card -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">الطلبات المغلقة</p>
                            <p class="text-3xl font-bold text-green-600">{{ props.statistics?.total_closed_orders || 0 }}</p>
                            <p class="text-xs text-muted-foreground mt-1">طلبات تم تسليمها أو إلغاؤها</p>
                        </div>
                        <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/20">
                            <CheckCircle2 class="h-8 w-8 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & View Section -->
            <div class="flex flex-col gap-3 bg-white p-4 rounded-lg border shadow-sm md:flex-row md:items-center md:justify-between">
                <div class="flex items-center flex-wrap gap-3">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-5 w-5 text-gray-500" />
                        <span class="text-sm font-medium text-gray-700">
                            {{ t('فلتر الطلبات:', 'Filter orders:') }}
                        </span>
                    </div>
                    <select
                        v-model="selectedStatus"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- View mode toggle -->
                    <div class="flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-1 text-xs font-medium">
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 transition"
                            :class="viewMode === 'cards' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                            @click="viewMode = 'cards'"
                        >
                            عرض كبطاقات
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-3 py-1 transition"
                            :class="viewMode === 'table' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                            @click="viewMode = 'table'"
                        >
                            عرض كجدول
                        </button>
                    </div>
                </div>
                <div class="text-sm text-gray-500">
                    <template v-if="ordersLang === 'ar'">
                    عرض {{ filteredOrders.length }} من {{ props.orders.length }} طلب
                    </template>
                    <template v-else>
                        Showing {{ filteredOrders.length }} of {{ props.orders.length }} orders
                    </template>
                </div>
            </div>

            <!-- Orders List: Cards View -->
            <!-- نجعل الكروت أعرض عبر تقليل عدد الأعمدة في الشاشات الكبيرة -->
            <div
                v-if="viewMode === 'cards'"
                class="grid gap-6 md:grid-cols-1 lg:grid-cols-2"
            >
                <div v-for="order in filteredOrders" :key="order.id" :class="[
                    'group relative overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-200 hover:shadow-lg hover:scale-[1.02]',
                    isNewOrder(order) ? 'ring-2 ring-green-400 ring-opacity-50 animate-pulse' : ''
                ]">
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
                        <div class="flex items-start space-x-3 mb-4">
                            <div class="rounded-lg p-3 bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900/20 dark:to-blue-900/20">
                                <ShoppingCart class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-foreground">{{ order.order_number }}</h3>
                                <p class="text-sm text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                                <!-- Shipping Status directly under order number -->
                                <div class="mt-1 flex items-center space-x-2 text-xs">
                                    <span class="text-gray-500">حالة الشحن:</span>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 font-semibold',
                                            getStatusColor(order.shipping_status || 'New Order')
                                        ]"
                                    >
                                        {{ getShippingStatusLabel(order.shipping_status || 'New Order') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!isDelivered(order)"
                            class="mt-2 flex items-center justify-end space-x-2 text-sm font-semibold"
                        >
                            <Timer class="h-4 w-4 text-red-600" />
                            <span class="text-xl font-bold text-red-600">{{ formatElapsedTime(order) }}</span>
                        </div>

                        <!-- Order Details -->
                        <div class="mt-4 space-y-4 border-t pt-4">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <!-- Customer Info -->
                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                        <User class="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">
                                            {{ t('العميل', 'Customer') }}
                                        </span>
                                        <span class="font-medium text-gray-900 truncate">
                                            {{ order.user.name }}
                                </span>
                                    </div>
                            </div>

                                <!-- Restaurant Info -->
                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100">
                                        <Store class="h-4 w-4 text-emerald-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">
                                            {{ t('المطعم', 'Restaurant') }}
                                        </span>
                                        <span class="font-medium text-gray-900 truncate">
                                            {{ order.restaurant.name }}
                                        </span>
                                    </div>
                            </div>

                                <!-- Order Source -->
                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100">
                                        <Link2 class="h-4 w-4 text-indigo-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">
                                            {{ t('مصدر الطلب', 'Order source') }}
                                        </span>
                                        <span class="font-medium text-gray-900">
                                            {{ getSourceLabel(order.source) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Zyda unique code (منصة زيدا) -->
                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100">
                                        <span class="text-xs font-bold text-indigo-700">Z</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">
                                            كود زيدا
                                        </span>
                                        <span class="font-mono text-sm font-medium text-gray-900">
                                            {{
                                                order.zyda_order && order.zyda_order.zyda_order_key
                                                    ? order.zyda_order.zyda_order_key
                                                    : 'null'
                                            }}
                                        </span>
                                    </div>
                                </div>

                            <!-- Driver Info -->
                            <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                                    <UserCircle2 class="h-4 w-4 text-orange-600" />
                                </div>
                                <div class="flex flex-col space-y-0.5">
                                    <!-- Driver Status -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">
                                            {{ t('حالة المندوب', 'Driver status') }}:
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                getStatusColor(getDriverStatusForOrder(order) || 'New Order')
                                            ]"
                                        >
                                            {{ getShippingStatusLabel(getDriverStatusForOrder(order) || 'New Order') }}
                                        </span>
                                    </div>

                                    <!-- Driver Name -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">
                                            {{ t('اسم المندوب', 'Driver name') }}:
                                        </span>
                                        <span
                                            :class="[
                                                'text-xs font-medium truncate',
                                                getDriverNameForOrder(order) ? 'text-gray-900' : 'text-gray-400 italic'
                                            ]"
                                        >
                                            {{ getDriverNameForOrder(order) || t('لم يتم التعيين بعد', 'Not assigned yet') }}
                                        </span>
                                    </div>

                                    <!-- Driver Phone -->
                                    <div class="flex items-center gap-2" v-if="getDriverPhoneForOrder(order)">
                                        <span class="text-xs text-gray-500">
                                            {{ t('رقم الجوال', 'Phone') }}:
                                        </span>
                                        <a
                                            class="text-xs font-medium text-blue-600 hover:underline"
                                            :href="`tel:${getDriverPhoneForOrder(order)}`"
                                        >
                                            {{ getDriverPhoneForOrder(order) }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                                <!-- Order Status -->
                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100">
                                        <AlertCircle class="h-4 w-4 text-yellow-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">
                                            {{ t('حالة الطلب', 'Order status') }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold mt-0.5 self-start',
                                                getStatusColor(order.status)
                                            ]"
                                        >
                                            {{ getOrderStatusLabel(order.status) }}
                                        </span>
                                    </div>
                            </div>

                            <!-- Items Count -->
                                <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100">
                                        <ShoppingCart class="h-4 w-4 text-purple-600" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">
                                            {{ t('عدد الأصناف', 'Items count') }}
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <span class="font-medium text-gray-900">
                                                {{ order.items_count || 0 }} items
                                            </span>
                                            <span v-if="order.items_subtotal" class="text-xs text-muted-foreground">
                                    ({{ formatCurrency(order.items_subtotal) }})
                                </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Footer -->
                    <div class="px-6 py-4 border-t bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl font-bold text-green-600">{{ formatCurrency(order.total) }}</span>
                            </div>
                            <div class="flex space-x-2">
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
                        </div>
                    </div>

                </div>
            </div>

            <!-- Orders List: Table View -->
            <div v-else class="rounded-lg border bg-card mt-4">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50 text-xs text-gray-500">
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('رقم الطلب', 'Order #') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('العميل', 'Customer') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('المطعم', 'Restaurant') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('مصدر الطلب', 'Source') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('حالة الشحن', 'Shipping status') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('حالة الطلب', 'Order status') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('الإجمالي', 'Total') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('التاريخ', 'Date') }}
                                </th>
                                <th class="h-10 px-3 text-right font-semibold">
                                    {{ t('الإجراءات', 'Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="order in filteredOrders"
                                :key="order.id"
                                class="border-b hover:bg-muted/40"
                            >
                                <td class="px-3 py-2 align-middle">
                                    <span class="font-semibold text-sm">
                                        {{ order.order_number }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ order.user.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ order.user.email }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ order.restaurant.name }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <span class="text-xs font-medium text-gray-700">
                                        {{ getSourceLabel(order.source) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                            getStatusColor(order.shipping_status || 'New Order')
                                        ]"
                                    >
                                        {{ getShippingStatusLabel(order.shipping_status || 'New Order') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                            getStatusColor(order.status)
                                        ]"
                                    >
                                        {{ getOrderStatusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="text-sm font-bold text-emerald-600">
                                        {{ formatCurrency(order.total) }}
                                    </div>
                                    <div v-if="order.items_count" class="text-[11px] text-muted-foreground">
                                        {{ order.items_count }} items
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="text-xs text-muted-foreground">
                                        {{ formatDate(order.created_at) }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                        <div class="flex flex-wrap gap-1 justify-end">
                                        <button
                                            v-if="order.shipping_status === 'New Order'"
                                            @click="acceptOrder(order.id)"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium bg-green-600 text-white hover:bg-green-700"
                                        >
                                            {{ t('قبول', 'Accept') }}
                                        </button>
                                        <Link
                                            :href="route('orders.show', order.id)"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium bg-blue-600 text-white hover:bg-blue-700"
                                        >
                                            {{ t('عرض', 'View') }}
                                        </Link>
                                        <Link
                                            :href="route('orders.edit', order.id)"
                                            class="rounded-lg border px-3 py-1.5 text-[11px] font-medium border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                        >
                                            {{ t('تعديل', 'Edit') }}
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State (for open orders) -->
            <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-12">
                <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">
                    {{ t('لا توجد طلبات', 'No orders found') }}
                </h3>
                <p class="mt-2 text-muted-foreground">
                    {{ t('ستظهر الطلبات هنا بمجرد بدء العملاء في الطلب.', 'Orders will appear here once customers start placing them.') }}
                </p>
                <Link
                    :href="route('orders.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    {{ t('إنشاء طلب', 'Create order') }}
                </Link>
            </div>

            <!-- Empty State for Filtered Results -->
            <div v-if="filteredOrders.length === 0 && props.orders.length > 0" class="flex flex-col items-center justify-center py-12">
                <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">
                    {{ t('لا توجد طلبات بهذه الحالة', 'No orders with this status') }}
                </h3>
                <p class="mt-2 text-muted-foreground">
                    {{ t('جرب اختيار حالة أخرى من الفلتر.', 'Try selecting another status from the filter.') }}
                </p>
            </div>

            <!-- Closed Orders Section -->
            <div v-if="closedOrders.length > 0" class="mt-10 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-foreground">
                            {{ t('الطلبات المغلقة', 'Closed orders') }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ t(
                                'الطلبات التي تم تسليمها أو إلغاؤها لا تظهر في قائمة الطلبات الحالية.',
                                'Orders that have been delivered or cancelled will not appear in the current orders list.'
                            ) }}
                        </p>
                    </div>
                    <div class="rounded-full bg-gray-100 px-4 py-1 text-xs font-medium text-gray-700">
                        <span v-if="ordersLang === 'ar'">
                            عدد الطلبات المغلقة: {{ closedOrders.length }}
                        </span>
                        <span v-else>
                            Closed orders: {{ closedOrders.length }}
                        </span>
                    </div>
                </div>

                <div class="rounded-lg border bg-card">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1000px] text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50 text-xs text-gray-500">
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('رقم الطلب', 'Order #') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('العميل', 'Customer') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('المطعم', 'Restaurant') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('حالة الشحن', 'Shipping status') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('حالة الطلب', 'Order status') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('الإجمالي', 'Total') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('تاريخ الإغلاق', 'Closed at') }}
                                    </th>
                                    <th class="h-10 px-3 text-right font-semibold">
                                        {{ t('الإجراءات', 'Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="order in closedOrders"
                                    :key="order.id"
                                    class="border-b hover:bg-muted/40"
                                >
                                    <td class="px-3 py-2 align-middle">
                                        <span class="font-semibold text-sm">
                                            {{ order.order_number }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ order.user.name }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ order.user.email }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ order.restaurant.name }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                getStatusColor(order.shipping_status || 'New Order')
                                            ]"
                                        >
                                            {{ getShippingStatusLabel(order.shipping_status || 'New Order') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                                getStatusColor(order.status)
                                            ]"
                                        >
                                            {{ getOrderStatusLabel(order.status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="text-sm font-bold text-emerald-600">
                                            {{ formatCurrency(order.total) }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="text-xs text-muted-foreground">
                                            {{ order.delivered_at ? formatDate(order.delivered_at) : '—' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 align-middle">
                                        <div class="flex justify-end gap-2">
                                            <Link
                                                :href="route('orders.show', order.id)"
                                                class="rounded-lg px-3 py-1.5 text-[11px] font-medium bg-blue-600 text-white hover:bg-blue-700"
                                            >
                                                {{ t('عرض', 'View') }}
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
