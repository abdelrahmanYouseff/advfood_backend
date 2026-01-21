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
    shipping_provider?: string | null;
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
    if (logoPath) {
        return `${baseUrl}${logoPath}`;
    }
    
    return null;
};

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
        // Original order statuses - Black and Gray only
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        confirmed: 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-100',
        preparing: 'bg-gray-300 text-gray-900 dark:bg-gray-600 dark:text-gray-100',
        ready: 'bg-gray-400 text-white dark:bg-gray-500 dark:text-white',
        delivering: 'bg-gray-500 text-white dark:bg-gray-400 dark:text-white',
        delivered: 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900',
        cancelled: 'bg-gray-900 text-white dark:bg-gray-200 dark:text-gray-900',
        // Shipping statuses - Black and Gray only
        'New Order': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        'Confirmed': 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-100',
        'Preparing': 'bg-gray-300 text-gray-900 dark:bg-gray-600 dark:text-gray-100',
        'Ready': 'bg-gray-400 text-white dark:bg-gray-500 dark:text-white',
        'Out for Delivery': 'bg-gray-500 text-white dark:bg-gray-400 dark:text-white',
        'Delivered': 'bg-gray-700 text-white dark:bg-gray-300 dark:text-gray-900',
        'Cancelled': 'bg-gray-900 text-white dark:bg-gray-200 dark:text-gray-900',
        'Accepted': 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-100',
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

const getShippingProviderLabel = (provider?: string | null) => {
    if (!provider) {
        return '—';
    }
    const normalizedProvider = provider.toLowerCase();
    const providerLabels: Record<string, string> = {
        'shadda': 'شدة',
        'leajlak': 'لاجلك',
        'shipping': 'لاجلك', // Legacy support
    };
    return providerLabels[normalizedProvider] ?? provider;
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

// Format currency with SAR as superscript
const formatCurrencyProfessional = (amount: number) => {
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
    return formatted;
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

// Sound notification for new orders - Enhanced with multiple fallback methods
const playNotificationSound = () => {
    if (!soundEnabled.value) {
        console.log('🔇 Sound is disabled');
        return;
    }

    console.log('🔊 Attempting to play notification sound...');

    // Method 1: Web Audio API (primary method)
    try {
        const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        // Create a loud, attention-grabbing notification sound
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.2);
        oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.3);

        gainNode.gain.setValueAtTime(0.5, audioContext.currentTime); // Louder volume
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.4);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.4);
        console.log('✅ Sound played using Web Audio API');
    } catch (error) {
        console.warn('⚠️ Web Audio API failed, trying fallback method:', error);
        
        // Method 2: HTML5 Audio fallback
        try {
            const audio = new Audio();
            // Create a data URL for a beep sound
            const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            const destination = audioContext.createMediaStreamDestination();
            
            oscillator.connect(gainNode);
            gainNode.connect(destination);
            
            oscillator.frequency.value = 1000;
            gainNode.gain.value = 0.5;
            
            const mediaRecorder = new MediaRecorder(destination.stream);
            const chunks: Blob[] = [];
            
            mediaRecorder.ondataavailable = (e) => chunks.push(e.data);
            mediaRecorder.onstop = () => {
                const blob = new Blob(chunks, { type: 'audio/ogg' });
                const url = URL.createObjectURL(blob);
                const audio = new Audio(url);
                audio.volume = 1.0;
                audio.play().catch(err => console.warn('Audio play failed:', err));
            };
            
            oscillator.start();
            mediaRecorder.start();
            setTimeout(() => {
                oscillator.stop();
                mediaRecorder.stop();
            }, 400);
        } catch (error2) {
            console.warn('⚠️ HTML5 Audio fallback also failed:', error2);
            
            // Method 3: Simple beep using system beep (if available)
            try {
                // Try to use system beep
                if (typeof (window as any).beep === 'function') {
                    (window as any).beep();
                } else {
                    // Last resort: Create a very simple beep
                    const ctx = new (window.AudioContext || (window as any).webkitAudioContext)();
                    const osc = ctx.createOscillator();
                    const g = ctx.createGain();
                    osc.connect(g);
                    g.connect(ctx.destination);
                    osc.frequency.value = 1000;
                    g.gain.value = 0.5;
                    osc.start();
                    osc.stop(ctx.currentTime + 0.3);
                }
            } catch (error3) {
                console.error('❌ All sound methods failed:', error3);
            }
        }
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
    const message = `Order Number ${dailyNumber}`;

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

    // Play sound first (multiple times to ensure it's heard)
    playNotificationSound();
    setTimeout(() => playNotificationSound(), 200);
    setTimeout(() => playNotificationSound(), 400);

    // Play announcement immediately
    setTimeout(() => {
    playOrderAnnouncement(order);
    }, 600);

    // Repeat every 5 seconds until order is accepted
    const intervalId = setInterval(() => {
        // Check if order still needs announcement
        const currentOrder = props.orders.find(o => o.id === order.id);
        if (!currentOrder || !isUnacceptedOrder(currentOrder)) {
            stopOrderAnnouncement(order.id);
            return;
        }
        // Play sound multiple times and announcement
        playNotificationSound();
        setTimeout(() => playNotificationSound(), 200);
        setTimeout(() => {
        playOrderAnnouncement(currentOrder);
        }, 400);
    }, 5000);

    announcementIntervals.set(order.id, intervalId);
    console.log(`🔔 Started announcement for order ${order.id} - will repeat every 5 seconds until accepted`);
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
    // IMPORTANT: Use props.orders (all orders) instead of filteredOrders to ensure
    // sound works for ALL orders regardless of status filter or branch
    // Get all new orders (created within last 5 minutes) that are still unaccepted
    const newOrders = props.orders.filter(
        (order: any) => isNewOrder(order) && isUnacceptedOrder(order)
    );

    // Get unaccepted orders (New Order status) - use all orders, not filtered
    const unacceptedOrders = props.orders.filter((order: any) => isUnacceptedOrder(order));

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
            // Play sound multiple times to ensure it's heard
            playNotificationSound();
            setTimeout(() => playNotificationSound(), 300);
            setTimeout(() => playNotificationSound(), 600);
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
        // Use props.orders to check all orders, not just filtered ones
        const fiveMinutesAgo = Date.now() - (5 * 60 * 1000);
        props.orders.forEach((order: any) => {
            const orderTime = new Date(order.created_at).getTime();
            if (orderTime < fiveMinutesAgo) {
                notifiedNewOrders.value.delete(order.id);
            }
        });
        return;
    }

    // Start announcement for ALL unaccepted orders (not just newest)
    // This ensures all pending orders get announced until accepted
    ordersNeedingSound.forEach((order: any) => {
        if (!announcementIntervals.has(order.id)) {
            console.log(`🔔 Starting announcement for order: ${order.id} (daily number: ${getDailyOrderNumber(order)})`);
            startOrderAnnouncement(order);
        }
    });

    // Stop announcements for orders that are no longer unaccepted
    announcementIntervals.forEach((intervalId, orderId) => {
        const order = props.orders.find((o: any) => o.id === orderId);
        if (!order || !isUnacceptedOrder(order)) {
            stopOrderAnnouncement(orderId);
        }
    });

    // Legacy continuous sound - keep for backward compatibility but prioritize voice announcements
    // Use props.orders to check all orders, not just filtered ones
    const ordersWithSound = props.orders.filter((order: any) => order.sound === true);
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
    // Use props.orders to check all orders, not just filtered ones
    props.orders.forEach((order: any) => {
        if (isNewOrder(order)) {
            notifiedNewOrders.value.add(order.id);
            // Start voice announcement for existing new orders on page load
            if (isUnacceptedOrder(order)) {
                console.log(`🔊 Starting voice announcement for existing new order on page load: ${order.id} (${order.order_number})`);
                setTimeout(() => {
                    startOrderAnnouncement(order);
                }, 1000); // Start after 1 second to allow page to fully load
            }
        }
    });

    // Start monitoring for orders with sound
    const stopMonitoring = monitorOrdersWithSound();

    // Start polling driver / shipping status from shipping company
    startDriverStatusPolling();

    // Set up polling for new orders every 3 seconds (real-time monitoring)
    const interval = setInterval(() => {
        console.log('🔄 Polling for new orders...');
        // Use props.orders to track all orders, not just filtered ones
        const previousOrderIds = new Set(props.orders.map((o: any) => o.id));
        router.reload({
            only: ['orders'],
            // @ts-expect-error preserveScroll exists at runtime but is missing from TS types
            preserveScroll: true,
            onSuccess: (page: any) => {
                console.log('✅ Orders reloaded, checking for new orders and sound...');
                // Check ALL orders from backend, not just filtered ones
                const currentOrderIds = new Set(page.props.orders.map((o: any) => o.id));

                // Check for new orders (orders that weren't in previous list)
                const newlyAddedOrders = page.props.orders.filter((order: any) => !previousOrderIds.has(order.id));
                if (newlyAddedOrders.length > 0) {
                    console.log(`🆕 Found ${newlyAddedOrders.length} new order(s) - Playing sound immediately!`);
                    // Play sound IMMEDIATELY for ALL new orders (before checking status)
                    newlyAddedOrders.forEach((order: any) => {
                        if (!notifiedNewOrders.value.has(order.id)) {
                            console.log(`🔔 Playing sound immediately for new order: ${order.id} (${order.order_number})`);
                            // Play sound multiple times to ensure it's heard
                            playNotificationSound();
                            setTimeout(() => playNotificationSound(), 300);
                            setTimeout(() => playNotificationSound(), 600);
                            notifiedNewOrders.value.add(order.id);
                            
                            // Start voice announcement immediately for new orders
                            if (isUnacceptedOrder(order)) {
                                console.log(`🔊 Starting voice announcement for new order: ${order.id} (${order.order_number})`);
                                setTimeout(() => {
                                    startOrderAnnouncement(order);
                                }, 800); // Start after sound plays
                            }
                        }
                    });
                    // Also check for orders with sound enabled and start announcements
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
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-800 text-white dark:bg-gray-700 dark:text-gray-200 animate-pulse">
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
                                soundEnabled ? 'bg-gray-800 dark:bg-gray-600' : 'bg-gray-300 dark:bg-gray-700'
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
                            class="ml-2 px-2 py-1 text-xs bg-gray-700 text-white rounded hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500"
                        >
                            اختبار الصوت
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('orders.create')"
                            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 transition"
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
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">الطلبات الجديدة</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ props.statistics?.total_new_orders || 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">طلبات لم يتم قبولها بعد</p>
                        </div>
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-700 p-3">
                            <AlertCircle class="h-8 w-8 text-gray-700 dark:text-gray-300" />
                        </div>
                    </div>
                </div>

                <!-- Total Closed Orders Card -->
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">الطلبات المغلقة</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ props.statistics?.total_closed_orders || 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">طلبات تم تسليمها أو إلغاؤها</p>
                        </div>
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-700 p-3">
                            <CheckCircle2 class="h-8 w-8 text-gray-700 dark:text-gray-300" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & View Section -->
            <div class="flex flex-col gap-3 bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm md:flex-row md:items-center md:justify-between">
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
            <div
                v-if="viewMode === 'cards'"
                class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5"
            >
                <div v-for="order in filteredOrders" :key="order.id" :class="[
                    'group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm transition-all duration-200 hover:shadow-md hover:scale-[1.01]',
                    isNewOrder(order) ? 'ring-2 ring-gray-700 ring-opacity-50 animate-pulse' : ''
                ]">
                    <!-- New Order Indicator -->
                    <div v-if="isNewOrder(order)" class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-gray-700 to-gray-900 z-10"></div>

                    <!-- New Order Badge -->
                    <div v-if="isNewOrder(order)" class="absolute top-2 left-2 z-10">
                        <span class="inline-flex items-center rounded-full bg-gray-900 px-1.5 py-0.5 text-[10px] font-medium text-white animate-bounce">
                            🆕 NEW
                        </span>
                    </div>

                    <!-- Order Header -->
                    <div class="p-3 pb-2">
                        <div class="flex items-start gap-2 mb-2">
                            <div class="rounded-md p-1.5 bg-gray-100 dark:bg-gray-800 flex-shrink-0">
                                <ShoppingCart class="h-4 w-4 text-gray-700 dark:text-gray-300" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-sm text-foreground truncate">{{ order.order_number }}</h3>
                                <p class="text-xs text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                                <!-- Shipping Status -->
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
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
                            class="mt-1.5 flex items-center justify-end gap-1.5 text-xs font-semibold"
                        >
                            <Timer class="h-3 w-3 text-red-600 dark:text-red-400" />
                            <span class="text-base font-bold text-red-600 dark:text-red-400">{{ formatElapsedTime(order) }}</span>
                        </div>

                        <!-- Order Details -->
                        <div class="mt-2.5 space-y-2 border-t border-gray-200 dark:border-gray-700 pt-2.5">
                            <div class="grid grid-cols-2 gap-2">
                                <!-- Customer Info -->
                                <div class="flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                        <User class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-[10px] text-gray-500 truncate">
                                            {{ t('العميل', 'Customer') }}
                                        </span>
                                        <span class="font-medium text-xs text-gray-900 dark:text-gray-100 truncate">
                                            {{ order.user.name }}
                                </span>
                                    </div>
                            </div>

                                <!-- Restaurant Info -->
                                <div v-if="getRestaurantImage(order.restaurant.name)" class="flex items-center justify-center rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full overflow-hidden flex-shrink-0 bg-gray-200 dark:bg-gray-700">
                                        <img 
                                            :src="getRestaurantImage(order.restaurant.name) || ''" 
                                            :alt="order.restaurant.name"
                                            class="h-full w-full object-cover rounded-full"
                                            @error="(e) => { 
                                                console.error('Failed to load restaurant image:', e.target.src);
                                                e.target.style.display = 'none';
                                            }"
                                            @load="(e) => {
                                                console.log('Restaurant image loaded successfully:', e.target.src);
                                            }"
                                        />
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                        <Store class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-[10px] text-gray-500 truncate">
                                            {{ t('المطعم', 'Restaurant') }}
                                        </span>
                                        <span class="font-medium text-xs text-gray-900 dark:text-gray-100 truncate">
                                            {{ order.restaurant.name }}
                                        </span>
                                    </div>
                            </div>

                                <!-- Order Source -->
                                <div class="flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                        <Link2 class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-[10px] text-gray-500 truncate">
                                            {{ t('مصدر', 'Source') }}
                                        </span>
                                        <span class="font-medium text-xs text-gray-900 dark:text-gray-100 truncate">
                                            {{ getSourceLabel(order.source) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Shipping Provider -->
                                <div class="flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                        <Truck class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-[10px] text-gray-500 truncate">
                                            {{ t('الشحن', 'Shipping') }}
                                        </span>
                                        <span class="font-medium text-xs text-gray-900 dark:text-gray-100 truncate">
                                            {{ getShippingProviderLabel(order.shipping_provider) }}
                                        </span>
                                    </div>
                                </div>

                            <!-- Driver Info -->
                                <div class="col-span-2 flex items-start gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0 mt-0.5">
                                        <UserCircle2 class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                </div>
                                    <div class="flex flex-col min-w-0 flex-1 space-y-0.5">
                                    <!-- Driver Status -->
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[10px] text-gray-500">
                                                {{ t('حالة', 'Status') }}:
                                        </span>
                                        <span
                                            :class="[
                                                    'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                                                getStatusColor(getDriverStatusForOrder(order) || 'New Order')
                                            ]"
                                        >
                                            {{ getShippingStatusLabel(getDriverStatusForOrder(order) || 'New Order') }}
                                        </span>
                                    </div>

                                    <!-- Driver Name -->
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] text-gray-500">
                                                {{ t('المندوب', 'Driver') }}:
                                        </span>
                                        <span
                                            :class="[
                                                    'text-[10px] font-medium truncate',
                                                    getDriverNameForOrder(order) ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 italic'
                                            ]"
                                        >
                                                {{ getDriverNameForOrder(order) || t('غير معين', 'N/A') }}
                                        </span>
                                    </div>

                                    <!-- Driver Phone -->
                                        <div class="flex items-center gap-1.5" v-if="getDriverPhoneForOrder(order)">
                                            <span class="text-[10px] text-gray-500">
                                                {{ t('هاتف', 'Phone') }}:
                                        </span>
                                        <a
                                                class="text-[10px] font-medium text-gray-700 dark:text-gray-300 hover:underline truncate"
                                            :href="`tel:${getDriverPhoneForOrder(order)}`"
                                        >
                                            {{ getDriverPhoneForOrder(order) }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                                <!-- Order Status -->
                                <div class="flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                        <AlertCircle class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-[10px] text-gray-500">
                                            {{ t('الحالة', 'Status') }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold mt-0.5 self-start',
                                                getStatusColor(order.status)
                                            ]"
                                        >
                                            {{ getOrderStatusLabel(order.status) }}
                                        </span>
                                    </div>
                            </div>

                            <!-- Items Count -->
                                <div class="flex items-center gap-1.5 rounded-md bg-gray-50 dark:bg-gray-800/50 px-2 py-1.5 border border-gray-200 dark:border-gray-700">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                                        <ShoppingCart class="h-3 w-3 text-gray-700 dark:text-gray-300" />
                                    </div>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-[10px] text-gray-500">
                                            {{ t('العناصر', 'Items') }}
                                        </span>
                                        <span class="font-medium text-xs text-gray-900 dark:text-gray-100">
                                            {{ order.items_count || 0 }}
                                </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Footer -->
                    <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-baseline">
                                <span class="text-base font-bold text-gray-900 dark:text-gray-100 inline-flex items-baseline">
                                    <sup class="text-[9px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-0.5">SAR</sup>
                                    <span>{{ formatCurrencyProfessional(order.total) }}</span>
                                </span>
                            </div>
                            <div class="flex gap-1.5">
                                <button
                                    v-if="order.shipping_status === 'New Order'"
                                    @click="acceptOrder(order.id)"
                                    class="rounded-md px-2 py-1 text-[10px] font-medium transition-colors bg-gray-800 text-white hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600"
                                >
                                    قبول
                                </button>
                                <Link
                                    :href="route('orders.show', order.id)"
                                    class="rounded-md px-2 py-1 text-[10px] font-medium transition-colors bg-gray-700 text-white hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500"
                                >
                                    عرض
                                </Link>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Orders List: Table View -->
            <div v-else class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 mt-4 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-sm">
                        <thead>
                            <tr class="border-b bg-gray-50 dark:bg-gray-800 text-xs text-gray-500">
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
                                    {{ t('شركة الشحن', 'Shipping Company') }}
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
                                class="border-b hover:bg-gray-50 dark:hover:bg-gray-700/50"
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
                                    <span class="text-xs font-medium text-gray-700">
                                        {{ getShippingProviderLabel(order.shipping_provider) }}
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
                                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100 inline-flex items-baseline">
                                        <sup class="text-[8px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-0.5">SAR</sup>
                                        <span>{{ formatCurrencyProfessional(order.total) }}</span>
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
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium bg-gray-800 text-white hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600"
                                        >
                                            {{ t('قبول', 'Accept') }}
                                        </button>
                                        <Link
                                            :href="route('orders.show', order.id)"
                                            class="rounded-lg px-3 py-1.5 text-[11px] font-medium bg-gray-700 text-white hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500"
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
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600"
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
                                <tr class="border-b bg-gray-50 dark:bg-gray-800 text-xs text-gray-500">
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
                                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-700/50"
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
                                        <div class="text-sm font-bold text-gray-700 dark:text-gray-300 inline-flex items-baseline">
                                            <sup class="text-[8px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-0.5">SAR</sup>
                                            <span>{{ formatCurrencyProfessional(order.total) }}</span>
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
