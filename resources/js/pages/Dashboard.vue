<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import {
    Store,
    ShoppingCart,
    DollarSign,
    Clock,
    TrendingUp,
    Package,
    Calendar,
    Save,
    Trash2,
    RefreshCcw,
    Copy,
    Check
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
        pending_count: number;
        received_count: number;
        pending_total: number;
        received_total: number;
        current_filter: string;
    };
    whatsapp_messages: Array<{
        id: number;
        deliver_order: string | null;
        location: string | null;
        created_at: string;
        updated_at: string;
    }>;
}

interface ZydaOrder {
    id: number;
    zyda_order_key: string | null;
    name: string | null;
    phone: string;
    address: string | null;
    location: string | null;
    total_amount: string;
    items: Array<Record<string, unknown>> | null;
    created_at: string | null;
    updated_at: string | null;
    order_id: number | null;
}

const props = defineProps<Props>();
const page = usePage();

// Dashboard language state (linked with sidebar language toggle)
const dashboardLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        dashboardLang.value = storedLang;
    }
}

// Keep dashboard language in sync with sidebar language toggle
onMounted(() => {
    if (typeof window === 'undefined') return;

    const handler = (event: Event) => {
        const lang = (event as CustomEvent).detail;
        if (lang === 'en' || lang === 'ar') {
            dashboardLang.value = lang;
        }
    };

    window.addEventListener('sidebar-lang-changed', handler as EventListener);

    onUnmounted(() => {
        window.removeEventListener('sidebar-lang-changed', handler as EventListener);
    });
});

// Simple translation helper
const t = (ar: string, en: string) => (dashboardLang.value === 'ar' ? ar : en);

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
        title: t('لوحة التحكم', 'Dashboard'),
        href: '/dashboard',
    },
];

const getStatusColor = (status: string) => {
    const colors = {
        // Original order statuses - Black and gray only
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        confirmed: 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200',
        preparing: 'bg-gray-300 text-gray-900 dark:bg-gray-600 dark:text-gray-100',
        ready: 'bg-gray-400 text-gray-900 dark:bg-gray-500 dark:text-gray-100',
        delivering: 'bg-gray-500 text-white dark:bg-gray-400 dark:text-gray-900',
        delivered: 'bg-gray-600 text-white dark:bg-gray-300 dark:text-gray-900',
        cancelled: 'bg-gray-700 text-white dark:bg-gray-200 dark:text-gray-900',
        // Shipping statuses - Black and gray only
        'New Order': 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        'Confirmed': 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-gray-200',
        'Preparing': 'bg-gray-300 text-gray-900 dark:bg-gray-600 dark:text-gray-100',
        'Ready': 'bg-gray-400 text-gray-900 dark:bg-gray-500 dark:text-gray-100',
        'Out for Delivery': 'bg-gray-500 text-white dark:bg-gray-400 dark:text-gray-900',
        'Delivered': 'bg-gray-600 text-white dark:bg-gray-300 dark:text-gray-900',
        'Cancelled': 'bg-gray-700 text-white dark:bg-gray-200 dark:text-gray-900',
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

// Format currency with SAR as superscript
const formatCurrencyProfessional = (amount: number) => {
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
    return formatted;
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

// Format Zyda total with SAR as superscript
const formatZydaTotalProfessional = (amount: number | string) => {
    const numeric = Number(amount ?? 0);
    return new Intl.NumberFormat('ar-SA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
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

const zydaOrdersCountLabel = computed(() => {
    const filter = zydaFilter.value;
    if (filter === 'received') {
        return `${props.zyda_summary?.received_count ?? 0} طلب`;
    }
    return `${props.zyda_summary?.pending_count ?? 0} طلب`;
});

const zydaOrdersTotalLabel = computed(() => {
    const filter = zydaFilter.value;
    if (filter === 'received') {
        return formatZydaTotal(props.zyda_summary?.received_total ?? 0);
    }
    return formatZydaTotal(props.zyda_summary?.pending_total ?? 0);
});

const zydaFilter = ref<string>(props.zyda_summary?.current_filter ?? 'pending');

const changeZydaFilter = (filter: 'pending' | 'received') => {
    zydaFilter.value = filter;
    router.get('/dashboard', { tab: 'zyda', zyda_filter: filter }, {
        only: ['zyda_orders', 'zyda_summary'],
        preserveState: true,
        preserveScroll: true,
    });
};

// State for editing locations
const editingLocations = ref<Record<number, string>>({});
const savingOrderId = ref<number | null>(null);
const deletingOrderId = ref<number | null>(null);
const autoSubmittingIds = ref<Set<number>>(new Set());

// State for sync
const syncLoading = ref(false);
const showSyncModal = ref(false);
const syncProgress = ref(0);
const syncMessage = ref('جاري التحميل...');
const syncLogs = ref<string[]>([]); // Store script output logs
const logsContainer = ref<HTMLElement | null>(null); // Reference to logs container for auto-scroll
const syncTimer = ref(0); // Timer in seconds
const syncTimerInterval = ref<number | null>(null); // Timer interval reference

// Initialize editing locations with current values
const initializeEditingLocations = () => {
    const locations: Record<number, string> = {};
    zydaOrders.value.forEach((order: ZydaOrder) => {
        locations[order.id] = order.location || '';
    });
    editingLocations.value = locations;
};

// Watch for changes in zydaOrders and initialize editing locations
watch(zydaOrders, () => {
    initializeEditingLocations();
    // Auto-submit Zyda orders that already have location and no linked Order
    autoSubmitZydaOrders();
}, { immediate: true });

// Update location function
const updateLocation = async (orderId: number) => {
    const location = editingLocations.value[orderId] || null;
    savingOrderId.value = orderId;

    try {
        // Get CSRF token from meta tag or cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || getCookie('XSRF-TOKEN')
            || '';

        const response = await fetch(`/api/zyda/orders/${orderId}/location`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                location: location || null,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to update location');
        }

        // Update the order location locally for better UX
        const order = zydaOrders.value.find((o: ZydaOrder) => o.id === orderId);
        if (order) {
            order.location = location;
        }

        // Check if order was created/updated (has order_id)
        const orderCreated = data.order_id || (data.data && data.data.order_id);

        if (orderCreated) {
            // Location saved and Order created from ZydaOrder
            alert('✅ تم حفظ الموقع وإنشاء الطلب بنجاح! سيتم تحديث قائمة طلبات Zyda.');
        } else {
            // Location saved only
            alert('✅ تم حفظ الموقع بنجاح!');
        }

        // بعد إنشاء الطلب، سيتم ربط zyda_order بـ order_id في الباك إند
        // DashboardController يعرض "قيد الانتظار" للطلبات التي ليس لها order_id فقط
        // لذلك نعيد تحميل بيانات تبويب Zyda مع نفس الفلتر الحالي
        changeZydaFilter(zydaFilter.value as 'pending' | 'received');
    } catch (error: any) {
        console.error('❌ Failed to update location:', error);
        alert(error.message || 'حدث خطأ أثناء حفظ الموقع. حاول مرة أخرى.');
    } finally {
        savingOrderId.value = null;
    }
};

// Auto-submit Zyda orders (no order_id yet) that already have a location
const autoSubmitZydaOrders = async () => {
    for (const order of zydaOrders.value) {
        if (!order.order_id && order.location) {
            if (autoSubmittingIds.value.has(order.id)) continue;
            autoSubmittingIds.value.add(order.id);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || getCookie('XSRF-TOKEN')
                    || '';

                const response = await fetch(`/api/zyda/orders/${order.id}/location`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        location: order.location,
                    }),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    console.error('❌ Auto submit location failed:', data);
                } else {
                    const orderCreated = data.order_id || (data.data && data.data.order_id);
                    if (orderCreated) {
                        // reflect locally
                        order.order_id = orderCreated;
                        // refresh Zyda tab to move it out of pending list
                        changeZydaFilter(zydaFilter.value as 'pending' | 'received');
                    }
                }
            } catch (error) {
                console.error('❌ Auto submit location error:', error);
            } finally {
                autoSubmittingIds.value.delete(order.id);
            }
        }
    }
};

// Delete Zyda order function
// Sync Zyda orders function
const syncZydaOrders = async () => {
    if (syncLoading.value) {
        return;
    }

    syncLoading.value = true;
    showSyncModal.value = true;
    syncProgress.value = 0;
    syncMessage.value = 'جاري التحميل...';
    syncLogs.value = []; // Clear previous logs
    syncTimer.value = 0; // Reset timer

    // Start timer
    syncTimerInterval.value = setInterval(() => {
        syncTimer.value += 1;
    }, 1000) as unknown as number;

    // Simulate progress
    const progressInterval = setInterval(() => {
        if (syncProgress.value < 90) {
            syncProgress.value += Math.random() * 10;
            if (syncProgress.value > 90) {
                syncProgress.value = 90;
            }
        }
    }, 500);

    try {
        // Use fetch directly with longer timeout for long-running scripts
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || getCookie('XSRF-TOKEN')
            || '';

        // Update progress more slowly after 90% while waiting for response
        const finalProgressInterval = setInterval(() => {
            if (syncProgress.value >= 90 && syncProgress.value < 95) {
                syncProgress.value += 0.5;
            }
        }, 1000);

        // Make request with longer timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 600000); // 10 minutes timeout

        try {
            const response = await fetch(route('orders.sync-zyda'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            clearTimeout(timeoutId);
            clearInterval(progressInterval);
            clearInterval(finalProgressInterval);

            // Stop timer
            if (syncTimerInterval.value !== null) {
                clearInterval(syncTimerInterval.value);
                syncTimerInterval.value = null;
            }

            console.log('📥 Response status:', response.status);
            const data = await response.json();
            console.log('📦 Response data:', data);

            if (!response.ok) {
                console.error('❌ Response not OK:', data);
                throw new Error(data.message || 'فشلت المزامنة');
            }

            // Parse and display script output
            console.log('📋 Output received:', data.output ? `${data.output.length} chars` : 'empty');

            if (data.output && data.output.trim().length > 0) {
                // Split output into lines and filter empty lines
                const outputLines = data.output.split('\n')
                    .map((line: string) => line.trim())
                    .filter((line: string) => line.length > 0);

                console.log('📝 Parsed output lines:', outputLines.length);
                syncLogs.value = outputLines;

                // Auto-scroll to bottom of logs
                setTimeout(() => {
                    if (logsContainer.value) {
                        logsContainer.value.scrollTop = logsContainer.value.scrollHeight;
                    }
                }, 100);

                // Update message with last few meaningful lines
                const lastLines = outputLines.slice(-5).join('\n');
                if (lastLines) {
                    syncMessage.value = lastLines.substring(0, 200); // Limit to 200 chars
                }
            } else {
                // If no output, show a message
                syncLogs.value = ['⚠️ لا يوجد output من السكريبت. قد يكون السكريبت لا يطبع أي شيء.'];
                console.warn('⚠️ No output from script');
            }

            // Success - Complete progress
            syncProgress.value = 100;

            // Show summary if available
            if (data.summary) {
                const summary = data.summary;
                syncMessage.value = `✅ تمت المزامنة بنجاح! تم إنشاء ${summary.created || 0} وتحديث ${summary.updated || 0} وتخطي ${summary.skipped || 0} (فشل ${summary.failed || 0})`;
            } else {
                syncMessage.value = data.message || '✅ تمت المزامنة بنجاح!';
            }

            // Close modal immediately and redirect to dashboard
            showSyncModal.value = false;
            syncLoading.value = false;
            syncProgress.value = 0;

            // Redirect to dashboard with zyda tab and reload data
            router.visit('/dashboard?tab=zyda', {
                only: ['zyda_orders', 'zyda_summary'],
                preserveState: false,
                preserveScroll: false,
            });

        } catch (fetchError: any) {
            clearTimeout(timeoutId);
            clearInterval(progressInterval);
            clearInterval(finalProgressInterval);

            // Stop timer
            if (syncTimerInterval.value !== null) {
                clearInterval(syncTimerInterval.value);
                syncTimerInterval.value = null;
            }

            console.error('❌ Sync fetch error:', fetchError);

            if (fetchError.name === 'AbortError') {
                syncProgress.value = 100;
                syncMessage.value = 'انتهى وقت الانتظار. يرجى المحاولة مرة أخرى.';
                syncLogs.value = ['❌ انتهى وقت الانتظار (10 دقائق).'];
            } else {
                syncProgress.value = 100;
                const errorMsg = fetchError.message || 'خطأ غير معروف';
                syncMessage.value = 'حدث خطأ أثناء المزامنة: ' + errorMsg;
                syncLogs.value = [
                    '❌ حدث خطأ أثناء المزامنة:',
                    errorMsg,
                    'يرجى التحقق من سجلات Laravel للحصول على مزيد من التفاصيل.'
                ];
            }

            setTimeout(() => {
                showSyncModal.value = false;
                syncLoading.value = false;
                syncProgress.value = 0;
                syncTimer.value = 0;
            }, 3000);
        }
    } catch (error: any) {
        clearInterval(progressInterval);

        // Stop timer
        if (syncTimerInterval.value !== null) {
            clearInterval(syncTimerInterval.value);
            syncTimerInterval.value = null;
        }

        syncProgress.value = 100;
        syncMessage.value = 'حدث خطأ أثناء المزامنة: ' + (error.message || 'خطأ غير معروف');
        console.error('❌ Sync error:', error);
        setTimeout(() => {
            showSyncModal.value = false;
            syncLoading.value = false;
            syncProgress.value = 0;
            syncTimer.value = 0;
        }, 3000);
    }
};

// Format timer as MM:SS
const formatTimer = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const deleteZydaOrder = async (orderId: number) => {
    if (!confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
        return;
    }

    deletingOrderId.value = orderId;

    try {
        // Get CSRF token from meta tag or cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || getCookie('XSRF-TOKEN')
            || '';

        const response = await fetch(`/api/zyda/orders/${orderId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to delete order');
        }

        // Reload to get updated data from server
        router.reload({
            only: ['zyda_orders', 'zyda_summary'],
            preserveScroll: true,
            onSuccess: () => {
                console.log('✅ Order deleted successfully!');
            },
        });
    } catch (error: any) {
        console.error('❌ Failed to delete order:', error);
        alert(error.message || 'حدث خطأ أثناء حذف الطلب. حاول مرة أخرى.');
    } finally {
        deletingOrderId.value = null;
    }
};

// Helper function to get cookie value
const getCookie = (name: string): string | null => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }
    return null;
};

// WhatsApp Messages - Copy location function
const copiedLocationId = ref<number | null>(null);
const deletingWhatsappMsgId = ref<number | null>(null);

const copyLocation = async (location: string | null, messageId: number) => {
    if (!location) {
        alert(t('لا يوجد موقع للنسخ', 'No location to copy'));
        return;
    }

    try {
        await navigator.clipboard.writeText(location);
        copiedLocationId.value = messageId;
        setTimeout(() => {
            copiedLocationId.value = null;
        }, 2000);
    } catch (error) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = location;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            copiedLocationId.value = messageId;
            setTimeout(() => {
                copiedLocationId.value = null;
            }, 2000);
        } catch (err) {
            alert(t('فشل نسخ الموقع', 'Failed to copy location'));
        }
        document.body.removeChild(textArea);
    }
};

const formatDateFull = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Delete WhatsApp message function
const deleteWhatsappMessage = async (messageId: number) => {
    if (!confirm(t('هل أنت متأكد من حذف هذه الرسالة؟', 'Are you sure you want to delete this message?'))) {
        return;
    }

    deletingWhatsappMsgId.value = messageId;

    try {
        // Get CSRF token from meta tag or cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || getCookie('XSRF-TOKEN')
            || '';

        const response = await fetch(`/api/whatsapp/messages/${messageId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Failed to delete message');
        }

        // Reload to get updated data from server
        router.reload({
            only: ['whatsapp_messages'],
            preserveScroll: true,
            onSuccess: () => {
                console.log('✅ WhatsApp message deleted successfully!');
            },
        });
    } catch (error: any) {
        console.error('❌ Failed to delete WhatsApp message:', error);
        alert(error.message || t('حدث خطأ أثناء حذف الرسالة. حاول مرة أخرى.', 'An error occurred while deleting the message. Please try again.'));
    } finally {
        deletingWhatsappMsgId.value = null;
    }
};
</script>

<template>
    <Head title="لوحة التحكم" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-gray-900">
                        {{ t('لوحة التحكم', 'Dashboard') }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ t('نظرة سريعة على أداء الطلبات والمطاعم.', 'Quick overview of orders and restaurant performance.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2 rounded-full border border-gray-200 bg-white p-1 text-sm font-medium">
                    <button
                        class="rounded-full px-4 py-2 transition"
                        :class="activeTab === 'overview' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'overview'"
                    >
                        {{ t('تقارير عامة', 'Overview') }}
                    </button>
                    <button
                        class="rounded-full px-4 py-2 transition"
                        :class="activeTab === 'zyda' ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        @click="activeTab = 'zyda'"
                    >
                        {{ t('طلبات Zyda', 'Zyda Orders') }}
                    </button>
                </div>
            </div>

            <div v-if="activeTab === 'overview'" class="space-y-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ t('المطاعم', 'Restaurants') }}
                                </p>
                                <p class="text-2xl font-bold">{{ stats.total_restaurants }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-800 p-3">
                                <Store class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ t('إجمالي الطلبات', 'Total Orders') }}
                                </p>
                                <p class="text-2xl font-bold">{{ stats.total_orders }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-800 p-3">
                                <ShoppingCart class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ t('إجمالي الإيرادات', 'Total Revenue') }}
                                </p>
                                <p class="text-2xl font-bold inline-flex items-baseline">
                                    <sup class="text-[10px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-1">SAR</sup>
                                    <span>{{ formatCurrencyProfessional(stats.total_revenue) }}</span>
                                </p>
                            </div>
                            <div class="rounded-lg bg-gray-800 p-3">
                                <DollarSign class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Stats -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ t('الطلبات المعلقة', 'Pending Orders') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-700">{{ stats.pending_orders }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-800 p-3">
                                <Clock class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ t('طلبات اليوم', 'Today\'s Orders') }}
                                </p>
                                <p class="text-2xl font-bold">{{ stats.today_orders }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-800 p-3">
                                <Calendar class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">
                                    {{ t('إيرادات اليوم', 'Today\'s Revenue') }}
                                </p>
                                <p class="text-2xl font-bold text-gray-700 inline-flex items-baseline">
                                    <sup class="text-[10px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-1">SAR</sup>
                                    <span>{{ formatCurrencyProfessional(stats.today_revenue) }}</span>
                                </p>
                            </div>
                            <div class="rounded-lg bg-gray-800 p-3">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Top Restaurants -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Recent Orders -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">
                                {{ t('الطلبات الأخيرة', 'Recent Orders') }}
                            </h3>
                            <Link
                                :href="route('orders.index')"
                                class="text-sm text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100"
                            >
                                {{ t('عرض الكل', 'View all') }}
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
                                    <div class="rounded-lg bg-gray-800 p-2">
                                        <Package class="h-4 w-4 text-white" />
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ order.order_number }}</p>
                                        <p class="text-sm text-muted-foreground">{{ order.user.name }} • {{ order.restaurant.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-700 inline-flex items-baseline">
                                        <sup class="text-[9px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-0.5">SAR</sup>
                                        <span>{{ formatCurrencyProfessional(order.total) }}</span>
                                    </p>
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
                            <h3 class="text-lg font-semibold">
                                {{ t('أفضل المطاعم', 'Top Restaurants') }}
                            </h3>
                            <Link
                                :href="route('restaurants.index')"
                                class="text-sm text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100"
                            >
                                عرض الكل
                            </Link>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(restaurant, index) in top_restaurants" :key="restaurant.id" class="flex items-center justify-between rounded-lg border p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-sm font-bold text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ restaurant.name }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            <span v-if="dashboardLang === 'ar'">{{ restaurant.orders_count }} طلب</span>
                                            <span v-else>{{ restaurant.orders_count }} orders</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="rounded-lg bg-gray-800 p-2">
                                    <Store class="h-4 w-4 text-white" />
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
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ t('طلبات Zyda', 'Zyda Orders') }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ t('عرض أحدث الطلبات المستوردة من منصة Zyda.', 'View the latest orders imported from Zyda platform.') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-gray-800"></span>
                                {{ t('إجمالي الطلبات', 'Total Orders') }}: {{ zydaOrdersCountLabel }}
                            </div>
                            <div class="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-gray-600"></span>
                                {{ t('إجمالي القيمة', 'Total Amount') }}: {{ zydaOrdersTotalLabel }}
                            </div>
                            <button
                                @click="syncZydaOrders"
                                :disabled="syncLoading"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 transition disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <RefreshCcw :class="['h-4 w-4', syncLoading ? 'animate-spin' : '']" />
                                {{ t('مزامنة Zyda', 'Sync Zyda') }}
                            </button>
                            <Link
                                href="/orders"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                {{ t('إدارة الطلبات', 'Manage Orders') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="mt-4 flex items-center gap-2 border-b border-gray-200">
                        <button
                            @click="changeZydaFilter('pending')"
                            :class="[
                                'px-4 py-2 text-sm font-medium transition rounded-t-lg border-b-2',
                                zydaFilter === 'pending'
                                    ? 'border-gray-800 text-gray-900 bg-gray-100'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            {{ t('قيد الانتظار', 'Pending') }} ({{ props.zyda_summary?.pending_count ?? 0 }})
                        </button>
                        <button
                            @click="changeZydaFilter('received')"
                            :class="[
                                'px-4 py-2 text-sm font-medium transition rounded-t-lg border-b-2',
                                zydaFilter === 'received'
                                    ? 'border-gray-800 text-gray-900 bg-gray-100'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            {{ t('مستلمة', 'Received') }} ({{ props.zyda_summary?.received_count ?? 0 }})
                        </button>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-[1100px] divide-y divide-gray-200 text-right text-sm table-auto">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('الاسم', 'Name') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('رقم الهاتف', 'Phone number') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('العنوان', 'Address') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('الموقع', 'Location') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('الإجمالي', 'Total') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('الأصناف', 'Items') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('الكود الفريد', 'Unique code') }}
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        {{ t('إجراءات', 'Actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="zydaOrders.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-sm text-muted-foreground">
                                        {{ t('لا توجد طلبات Zyda مسجلة حتى الآن.', 'No Zyda orders recorded yet.') }}
                                    </td>
                                </tr>
                                <tr v-for="order in zydaOrders" :key="order.id" class="hover:bg-gray-50">
                                    <!-- Name -->
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs font-semibold text-gray-900 truncate">
                                                {{ order.name ?? '—' }}
                                            </span>
                                            <span class="text-[11px] text-gray-500 font-mono">
                                                ID: {{ order.id }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Phone -->
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800">
                                            {{ order.phone }}
                                        </span>
                                    </td>

                                    <!-- Address -->
                                    <td class="px-4 py-3 text-gray-700 max-w-xs">
                                        <div class="text-xs leading-snug line-clamp-2">
                                            {{ order.address ?? '—' }}
                                        </div>
                                    </td>

                                    <!-- Location (editable) -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <input
                                                v-model="editingLocations[order.id]"
                                                type="text"
                                                :placeholder="order.location || t('أدخل الموقع', 'Enter location')"
                                                class="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-xs text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            />
                                            <button
                                                @click="updateLocation(order.id)"
                                                :disabled="savingOrderId === order.id"
                                                class="inline-flex items-center gap-1 rounded-md bg-gray-800 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <Save class="h-3.5 w-3.5" />
                                                <span v-if="savingOrderId !== order.id">
                                                    {{ t('حفظ', 'Save') }}
                                                </span>
                                                <span v-else>...</span>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Total -->
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-xs font-bold text-gray-700 inline-flex items-baseline">
                                            <sup class="text-[7px] font-normal text-gray-500 dark:text-gray-400 leading-none mr-0.5">SAR</sup>
                                            <span>{{ formatZydaTotalProfessional(order.total_amount) }}</span>
                                        </div>
                                    </td>

                                    <!-- Items -->
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="text-[11px] leading-snug">
                                            {{ formatZydaItems(order.items) }}
                                        </div>
                                    </td>

                                    <!-- Unique Code (single line, full) -->
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1 text-[11px] font-mono text-white whitespace-nowrap"
                                            :title="order.zyda_order_key ?? '—'"
                                        >
                                            {{ order.zyda_order_key ?? '—' }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3">
                                        <button
                                            @click="deleteZydaOrder(order.id)"
                                            :disabled="deletingOrderId === order.id"
                                            class="inline-flex items-center gap-1 rounded-md bg-gray-700 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
                                            :title="t('حذف الطلب', 'Delete order')"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            <span v-if="deletingOrderId !== order.id">
                                                {{ t('حذف', 'Delete') }}
                                            </span>
                                            <span v-else>...</span>
                                        </button>
                                    </td>
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

                    <!-- WhatsApp Messages Table -->
                    <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">
                                    {{ t('رسائل الواتساب', 'WhatsApp Messages') }}
                                </h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ t('عرض أحدث الرسائل المستلمة من الواتساب.', 'View the latest messages received from WhatsApp.') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-[800px] w-full divide-y divide-gray-200 text-right text-sm table-auto">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-right">
                                            {{ t('ID', 'ID') }}
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            {{ t('رسالة التسليم', 'Deliver Order') }}
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            {{ t('الموقع', 'Location') }}
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            {{ t('التاريخ', 'Date') }}
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            {{ t('إجراءات', 'Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-if="props.whatsapp_messages?.length === 0">
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted-foreground">
                                            {{ t('لا توجد رسائل واتساب مسجلة حتى الآن.', 'No WhatsApp messages recorded yet.') }}
                                        </td>
                                    </tr>
                                    <tr v-for="message in props.whatsapp_messages" :key="message.id" class="hover:bg-gray-50">
                                        <!-- ID -->
                                        <td class="px-4 py-3">
                                            <span class="text-xs font-mono text-gray-600">
                                                #{{ message.id }}
                                            </span>
                                        </td>

                                        <!-- Deliver Order -->
                                        <td class="px-4 py-3 text-gray-700 max-w-xs">
                                            <div class="text-xs leading-snug line-clamp-3">
                                                {{ message.deliver_order ?? '—' }}
                                            </div>
                                        </td>

                                        <!-- Location -->
                                        <td class="px-4 py-3 text-gray-700 max-w-md">
                                            <div v-if="message.location" class="text-xs leading-snug line-clamp-2 break-all">
                                                <a
                                                    :href="message.location"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline"
                                                >
                                                    {{ message.location }}
                                                </a>
                                            </div>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>

                                        <!-- Date -->
                                        <td class="px-4 py-3 text-gray-600">
                                            <div class="text-xs">
                                                {{ formatDateFull(message.created_at) }}
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    v-if="message.location"
                                                    @click="copyLocation(message.location, message.id)"
                                                    class="inline-flex items-center gap-1 rounded-md bg-gray-800 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-900"
                                                    :title="t('نسخ الموقع', 'Copy location')"
                                                >
                                                    <Check v-if="copiedLocationId === message.id" class="h-3.5 w-3.5" />
                                                    <Copy v-else class="h-3.5 w-3.5" />
                                                    <span v-if="copiedLocationId === message.id">
                                                        {{ t('تم النسخ', 'Copied') }}
                                                    </span>
                                                    <span v-else>
                                                        {{ t('نسخ', 'Copy') }}
                                                    </span>
                                                </button>
                                                <button
                                                    @click="deleteWhatsappMessage(message.id)"
                                                    :disabled="deletingWhatsappMsgId === message.id"
                                                    class="inline-flex items-center gap-1 rounded-md bg-gray-700 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-gray-900 disabled:cursor-not-allowed disabled:opacity-50"
                                                    :title="t('حذف الرسالة', 'Delete message')"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                    <span v-if="deletingWhatsappMsgId !== message.id">
                                                        {{ t('حذف', 'Delete') }}
                                                    </span>
                                                    <span v-else>...</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Modal -->
        <div v-if="showSyncModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
            <div class="relative w-full max-w-3xl mx-4">
                <div class="bg-white rounded-2xl shadow-2xl p-6 max-h-[90vh] flex flex-col">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-3">
                            <RefreshCcw :class="['h-8 w-8 text-gray-800', syncLoading ? 'animate-spin' : '']" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            {{ t('جاري المزامنة', 'Sync in progress') }}
                        </h3>
                        <div class="flex items-center justify-center gap-2 mt-2">
                            <Clock class="h-4 w-4 text-gray-800" />
                            <span class="text-sm font-mono text-gray-600 font-semibold">
                                {{ t('الوقت المستغرق:', 'Elapsed time:') }} {{ formatTimer(syncTimer) }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-gray-600 to-gray-800 rounded-full transition-all duration-300 ease-out"
                                :style="{ width: syncProgress + '%' }"
                            >
                                <div class="h-full bg-white bg-opacity-30 animate-pulse"></div>
                            </div>
                        </div>
                        <div class="mt-2 text-center">
                            <span class="text-sm font-medium text-gray-700">{{ Math.round(syncProgress) }}%</span>
                        </div>
                    </div>

                    <!-- Script Output Logs -->
                    <div class="flex-1 overflow-hidden mb-4">
                        <div ref="logsContainer" class="bg-gray-900 rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm">
                            <div v-if="syncLogs.length === 0" class="text-gray-400">
                                <div class="flex justify-center space-x-2 mb-2">
                                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                    <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                                </div>
                                <p class="text-center">
                                    {{ t('جاري تشغيل السكريبت...', 'Running sync script...') }}
                                </p>
                            </div>
                            <div v-else class="space-y-1">
                                <div
                                    v-for="(log, index) in syncLogs"
                                    :key="index"
                                    :class="[
                                        'text-left whitespace-pre-wrap break-words',
                                        log.includes('[ERROR]') || log.includes('ERROR') || log.includes('❌') ? 'text-red-400' :
                                        log.includes('[SUCCESS]') || log.includes('SUCCESS') || log.includes('✅') ? 'text-green-400' :
                                        log.includes('[WARN]') || log.includes('WARN') || log.includes('⚠️') ? 'text-yellow-400' :
                                        log.includes('[STEP]') || log.includes('STEP') ? 'text-blue-400' :
                                        log.includes('[INFO]') || log.includes('INFO') ? 'text-cyan-400' :
                                        log.includes('ORDER #') || log.includes('============================================================') ? 'text-green-300 font-bold' :
                                        log.includes('SUMMARY') ? 'text-yellow-300 font-bold' :
                                        'text-gray-300'
                                    ]"
                                >
                                    {{ log }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Message -->
                    <div class="text-center">
                        <p class="text-sm font-medium text-gray-700 bg-gray-50 rounded-lg p-3">
                            {{ syncMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
