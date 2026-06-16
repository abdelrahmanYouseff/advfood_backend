<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ArrowLeft, ChefHat, Clock, Volume2, VolumeX, WifiOff } from 'lucide-vue-next';

interface KitchenOrderItem {
    id: number;
    item_name: string;
    quantity: number;
    special_instructions?: string | null;
}

interface KitchenOrder {
    id: number;
    order_number: string;
    website_order_code?: string | null;
    status: string;
    shipping_status: string | null;
    total: number;
    sound: boolean;
    is_test?: boolean;
    created_at: string;
    special_instructions?: string | null;
    delivery_name?: string | null;
    restaurant: { name: string };
    order_items: KitchenOrderItem[];
}

interface Props {
    orders: KitchenOrder[];
}

const props = defineProps<Props>();

const kitchenLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        kitchenLang.value = storedLang;
    }
}

const t = (ar: string, en: string) => (kitchenLang.value === 'ar' ? ar : en);

/** Primary labels show both languages for mixed kitchen teams. */
const tBoth = (ar: string, en: string) => `${ar} · ${en}`;

const setKitchenLang = (lang: 'ar' | 'en') => {
    kitchenLang.value = lang;
    if (typeof window !== 'undefined') {
        window.localStorage.setItem('sidebarLang', lang);
        window.dispatchEvent(new CustomEvent('sidebar-lang-changed', { detail: lang }));
    }
};

const processingOrderId = ref<number | null>(null);
const notifiedOrderIds = ref<Set<number>>(new Set());
const soundEnabled = ref(true);
const connectionLost = ref(false);
const isRefreshing = ref(false);

const isNewOrder = (order: KitchenOrder) => {
    const shipping = (order.shipping_status ?? '').toLowerCase();
    const status = (order.status ?? '').toLowerCase();
    return shipping === 'new order' || status === 'pending' || order.sound;
};

const sortedOrders = computed(() => {
    return [...props.orders].sort((a, b) => {
        const aPriority = isNewOrder(a) ? 0 : 1;
        const bPriority = isNewOrder(b) ? 0 : 1;
        if (aPriority !== bPriority) {
            return aPriority - bPriority;
        }
        return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
    });
});

const newOrdersCount = computed(() => props.orders.filter(isNewOrder).length);

const statusLabelPairs: Record<string, [string, string]> = {
    pending: ['طلب جديد', 'New order'],
    confirmed: ['مؤكد', 'Confirmed'],
    preparing: ['قيد التحضير', 'Preparing'],
    ready: ['جاهز', 'Ready'],
    delivering: ['خارج للتوصيل', 'Out for delivery'],
    'new order': ['طلب جديد', 'New order'],
    'out for delivery': ['خارج للتوصيل', 'Out for delivery'],
};

const getOrderStatusKey = (order: KitchenOrder) => {
    const shipping = (order.shipping_status ?? '').toLowerCase();
    if (shipping && statusLabelPairs[shipping]) {
        return shipping;
    }
    return (order.status ?? '').toLowerCase();
};

const getStatusLabelParts = (order: KitchenOrder): [string, string] | null => {
    const key = getOrderStatusKey(order);
    return statusLabelPairs[key] ?? null;
};

const getCardHeaderClasses = (order: KitchenOrder) => {
    if (order.is_test) {
        return 'border-b-4 border-red-700 bg-red-600 text-white';
    }

    if (isNewOrder(order)) {
        return 'border-b-4 border-black bg-yellow-400 text-black';
    }

    const key = getOrderStatusKey(order);
    const byStatus: Record<string, string> = {
        pending: 'border-b-4 border-amber-500 bg-amber-50 text-gray-900',
        'new order': 'border-b-4 border-amber-500 bg-amber-50 text-gray-900',
        confirmed: 'border-b-4 border-sky-500 bg-sky-50 text-gray-900',
        preparing: 'border-b-4 border-orange-500 bg-orange-50 text-gray-900',
        ready: 'border-b-4 border-emerald-500 bg-emerald-50 text-gray-900',
        delivering: 'border-b-4 border-violet-500 bg-violet-50 text-gray-900',
        'out for delivery': 'border-b-4 border-violet-500 bg-violet-50 text-gray-900',
    };

    return byStatus[key] ?? 'border-b-4 border-gray-400 bg-white text-gray-900';
};

const getStatusBadgeClasses = (order: KitchenOrder) => {
    if (order.is_test) {
        return 'bg-white text-red-700';
    }

    if (isNewOrder(order)) {
        return 'bg-black text-yellow-400';
    }

    const key = getOrderStatusKey(order);
    const byStatus: Record<string, string> = {
        pending: 'bg-amber-600 text-white',
        'new order': 'bg-amber-600 text-white',
        confirmed: 'bg-sky-600 text-white',
        preparing: 'bg-orange-600 text-white',
        ready: 'bg-emerald-600 text-white',
        delivering: 'bg-violet-600 text-white',
        'out for delivery': 'bg-violet-600 text-white',
    };

    return byStatus[key] ?? 'bg-gray-800 text-white';
};

const getCardMetaTextClass = (order: KitchenOrder) => {
    if (order.is_test) {
        return 'text-white/80';
    }

    return isNewOrder(order) ? 'text-black/70' : 'text-gray-600';
};

const getTimeAgoParts = (dateString: string): { ar: string; en: string } => {
    const date = new Date(dateString);
    const diffMinutes = Math.floor((Date.now() - date.getTime()) / 60000);
    if (diffMinutes < 1) {
        return { ar: 'الآن', en: 'Just now' };
    }
    if (diffMinutes < 60) {
        return { ar: `منذ ${diffMinutes} د`, en: `${diffMinutes} min ago` };
    }
    const hours = Math.floor(diffMinutes / 60);
    return { ar: `منذ ${hours} س`, en: `${hours} hr ago` };
};

type NextAction =
    | { type: 'accept'; labelAr: string; labelEn: string }
    | { type: 'status'; status: string; labelAr: string; labelEn: string }
    | null;

const getNextAction = (order: KitchenOrder): NextAction => {
    const shipping = order.shipping_status ?? '';
    const status = (order.status ?? '').toLowerCase();

    if (shipping === 'New Order' || status === 'pending') {
        return { type: 'accept', labelAr: 'قبول الطلب', labelEn: 'Accept order' };
    }
    if (shipping === 'Confirmed' || status === 'confirmed') {
        return { type: 'status', status: 'preparing', labelAr: 'بدء التحضير', labelEn: 'Start preparing' };
    }
    if (shipping === 'Preparing' || status === 'preparing') {
        return { type: 'status', status: 'ready', labelAr: 'جاهز', labelEn: 'Ready' };
    }
    if (shipping === 'Ready' || status === 'ready') {
        return { type: 'status', status: 'delivering', labelAr: 'جاهز للتوصيل', labelEn: 'Out for delivery' };
    }
    return null;
};

const getActionButtonClass = (order: KitchenOrder) => {
    const action = getNextAction(order);
    if (!action) return 'bg-gray-400';
    if (action.type === 'accept') return 'bg-green-600 hover:bg-green-700 active:bg-green-800';
    if (action.type === 'status' && action.status === 'preparing') {
        return 'bg-orange-500 hover:bg-orange-600 active:bg-orange-700';
    }
    if (action.type === 'status' && action.status === 'ready') {
        return 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800';
    }
    return 'bg-purple-600 hover:bg-purple-700 active:bg-purple-800';
};

const getActionLabel = (order: KitchenOrder) => {
    const action = getNextAction(order);
    if (!action) return null;
    return tBoth(action.labelAr, action.labelEn);
};

const playNotificationSound = () => {
    if (!soundEnabled.value) return;

    try {
        const audioContext = new (window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.2);
        oscillator.frequency.setValueAtTime(1200, audioContext.currentTime + 0.3);

        gainNode.gain.setValueAtTime(0.6, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch {
        // Browser may block audio until user interaction
    }
};

const playNewOrderAlert = () => {
    playNotificationSound();
    setTimeout(playNotificationSound, 350);
    setTimeout(playNotificationSound, 700);
};

const shouldAlertForOrder = (order: KitchenOrder) => isNewOrder(order);

const acceptOrder = (orderId: number) => {
    processingOrderId.value = orderId;
    router.patch(route('orders.accept', orderId), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingOrderId.value = null;
        },
        onSuccess: () => {
            router.reload({ only: ['orders'], preserveScroll: true });
        },
    });
};

const updateOrderStatus = (orderId: number, status: string) => {
    processingOrderId.value = orderId;
    router.post(route('orders.update-status', orderId), { status }, {
        preserveScroll: true,
        onFinish: () => {
            processingOrderId.value = null;
        },
        onSuccess: () => {
            router.reload({ only: ['orders'], preserveScroll: true });
        },
    });
};

const handleAction = (order: KitchenOrder) => {
    const action = getNextAction(order);
    if (!action || processingOrderId.value !== null) return;

    if (action.type === 'accept') {
        acceptOrder(order.id);
        return;
    }
    updateOrderStatus(order.id, action.status);
};

const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value;
    localStorage.setItem('kitchenSoundEnabled', soundEnabled.value.toString());
};

const processReloadedOrders = (page: { props: { orders?: KitchenOrder[] } }, previousIds: Set<number>) => {
    const currentOrders = (page.props.orders ?? []) as KitchenOrder[];

    const newlyAdded = currentOrders.filter((o) => !previousIds.has(o.id));
    newlyAdded.forEach((order) => {
        if (!notifiedOrderIds.value.has(order.id)) {
            notifiedOrderIds.value.add(order.id);
            playNewOrderAlert();
        }
    });

    currentOrders.forEach((order) => {
        if (shouldAlertForOrder(order) && order.sound && !notifiedOrderIds.value.has(order.id)) {
            notifiedOrderIds.value.add(order.id);
            playNewOrderAlert();
        }
    });
};

const pollOrders = () => {
    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        connectionLost.value = true;
        return;
    }

    const previousIds = new Set(props.orders.map((o) => o.id));
    router.reload({
        only: ['orders'],
        preserveScroll: true,
        onSuccess: (page) => {
            connectionLost.value = false;
            processReloadedOrders(page, previousIds);
        },
        onError: () => {
            connectionLost.value = true;
        },
    });
};

const refreshKitchen = () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;
    const previousIds = new Set(props.orders.map((o) => o.id));

    router.reload({
        only: ['orders'],
        preserveScroll: true,
        onSuccess: (page) => {
            connectionLost.value = false;
            processReloadedOrders(page, previousIds);
        },
        onError: () => {
            connectionLost.value = true;
        },
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

onMounted(() => {
    const saved = localStorage.getItem('kitchenSoundEnabled');
    if (saved !== null) {
        soundEnabled.value = saved === 'true';
    }

    props.orders.forEach((order) => {
        if (shouldAlertForOrder(order)) {
            notifiedOrderIds.value.add(order.id);
        }
    });

    const handler = (event: Event) => {
        const lang = (event as CustomEvent).detail;
        if (lang === 'en' || lang === 'ar') {
            kitchenLang.value = lang;
        }
    };
    window.addEventListener('sidebar-lang-changed', handler as EventListener);

    const unlockAudio = async () => {
        try {
            const ctx = new (window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext)();
            await ctx.resume();
            await ctx.close();
        } catch {
            // Browser may block until user gesture
        }
        document.removeEventListener('click', unlockAudio);
        document.removeEventListener('touchstart', unlockAudio);
    };
    document.addEventListener('click', unlockAudio, { once: true });
    document.addEventListener('touchstart', unlockAudio, { once: true });

    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        connectionLost.value = true;
    }

    const onOffline = () => {
        connectionLost.value = true;
    };
    window.addEventListener('offline', onOffline);

    const pollInterval = setInterval(pollOrders, 3000);

    onUnmounted(() => {
        clearInterval(pollInterval);
        window.removeEventListener('offline', onOffline);
        window.removeEventListener('sidebar-lang-changed', handler as EventListener);
    });
});
</script>

<template>
    <Head :title="t('المطبخ', 'Kitchen')" />

    <div
        class="flex min-h-screen flex-col bg-gray-100"
        :dir="kitchenLang === 'ar' ? 'rtl' : 'ltr'"
        :lang="kitchenLang"
    >
        <!-- Kitchen header -->
        <header class="sticky top-0 z-40 border-b-4 border-black bg-yellow-400 px-4 py-4 shadow-md md:px-8">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <ChefHat class="h-10 w-10 shrink-0 text-black" aria-hidden="true" />
                    <div>
                        <h1 class="text-2xl font-bold text-black md:text-3xl">
                            {{ tBoth('المطبخ', 'Kitchen') }}
                        </h1>
                        <p class="text-sm font-medium text-black/80">
                            {{ tBoth(`${orders.length} طلب نشط`, `${orders.length} active orders`) }}
                            <span
                                v-if="newOrdersCount > 0"
                                class="ms-2 rounded-full bg-black px-3 py-0.5 text-yellow-400"
                            >
                                {{ tBoth(`${newOrdersCount} جديد`, `${newOrdersCount} new`) }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Language (kitchen has no sidebar) -->
                    <div class="flex items-center gap-1 rounded-xl border-2 border-black bg-white p-1 text-sm font-bold">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 transition"
                            :class="kitchenLang === 'ar' ? 'bg-black text-yellow-400' : 'text-black hover:bg-gray-100'"
                            @click="setKitchenLang('ar')"
                        >
                            العربية
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 transition"
                            :class="kitchenLang === 'en' ? 'bg-black text-yellow-400' : 'text-black hover:bg-gray-100'"
                            @click="setKitchenLang('en')"
                        >
                            English
                        </button>
                    </div>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl border-2 border-black bg-white px-4 py-3 text-base font-bold text-black transition hover:bg-gray-50"
                        @click="toggleSound"
                    >
                        <Volume2 v-if="soundEnabled" class="h-6 w-6 shrink-0" />
                        <VolumeX v-else class="h-6 w-6 shrink-0" />
                        {{ soundEnabled ? tBoth('الصوت مفعل', 'Sound on') : tBoth('الصوت معطل', 'Sound off') }}
                    </button>
                    <Link
                        :href="route('orders.index')"
                        class="flex items-center gap-2 rounded-xl border-2 border-black bg-black px-5 py-3 text-base font-bold text-yellow-400 transition hover:bg-gray-900"
                    >
                        <ArrowLeft class="h-6 w-6 shrink-0" :class="kitchenLang === 'ar' ? '' : 'rotate-180'" />
                        {{ tBoth('خروج', 'Exit') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Connection lost -->
        <div
            v-if="connectionLost"
            role="alert"
            class="border-b-4 border-red-700 bg-red-50 px-4 py-5 shadow-md md:px-8"
        >
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-4 sm:flex-row sm:justify-between">
                <div class="flex items-center gap-3 text-center sm:text-start">
                    <WifiOff class="h-8 w-8 shrink-0 text-red-700" aria-hidden="true" />
                    <p class="text-lg font-bold text-red-900 md:text-xl">
                        {{
                            tBoth(
                                'انقطع الاتصال — تعذر تحديث الطلبات',
                                'Connection lost — unable to refresh orders'
                            )
                        }}
                    </p>
                </div>
                <button
                    type="button"
                    class="w-full shrink-0 rounded-xl bg-red-600 px-8 py-4 text-xl font-black text-white shadow-md transition hover:bg-red-700 active:bg-red-800 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    :disabled="isRefreshing"
                    @click="refreshKitchen"
                >
                    {{
                        isRefreshing
                            ? t('جاري التحديث...', 'Refreshing...')
                            : tBoth('يرجى التحديث', 'Please refresh')
                    }}
                </button>
            </div>
        </div>

        <!-- Orders grid -->
        <main class="flex-1 p-4 md:p-8">
            <div
                v-if="sortedOrders.length > 0"
                class="mx-auto grid max-w-7xl grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="order in sortedOrders"
                    :key="order.id"
                    :class="[
                        'flex flex-col overflow-hidden rounded-2xl border-4 bg-white shadow-lg transition',
                        order.is_test
                            ? 'border-red-600 ring-4 ring-red-300'
                            : isNewOrder(order)
                              ? 'animate-pulse border-yellow-400 ring-4 ring-yellow-300'
                              : 'border-gray-200',
                    ]"
                >
                    <!-- Card header -->
                    <div class="px-5 py-4" :class="getCardHeaderClasses(order)">
                        <p
                            v-if="order.is_test"
                            class="mb-3 inline-block rounded-xl border-4 border-white bg-white px-5 py-2 text-3xl font-black uppercase tracking-[0.2em] text-red-700 shadow-lg"
                        >
                            TEST
                        </p>

                        <div class="flex items-start justify-between gap-3">
                            <p
                                class="text-sm font-bold uppercase tracking-wide"
                                :class="getCardMetaTextClass(order)"
                            >
                                <span dir="rtl">طلب</span>
                                <span aria-hidden="true"> · </span>
                                <span dir="ltr">Order</span>
                            </p>
                            <p class="shrink-0 text-end text-lg font-bold text-gray-900">
                                {{ order.restaurant.name }}
                            </p>
                        </div>

                        <p
                            class="mt-2 block w-full break-all text-3xl font-black leading-tight tabular-nums"
                            :class="order.is_test ? 'text-white' : 'text-gray-900'"
                            dir="ltr"
                        >
                            #{{ order.order_number }}
                        </p>
                        <p
                            v-if="order.website_order_code"
                            class="mt-1 block w-full break-all text-lg font-bold text-gray-800"
                            dir="ltr"
                        >
                            {{ order.website_order_code }}
                        </p>

                        <p
                            class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold"
                            :class="getCardMetaTextClass(order)"
                        >
                            <Clock class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span dir="rtl">{{ getTimeAgoParts(order.created_at).ar }}</span>
                            <span aria-hidden="true" class="text-gray-400">·</span>
                            <span dir="ltr">{{ getTimeAgoParts(order.created_at).en }}</span>
                        </p>

                        <p
                            class="mt-3 inline-block rounded-lg px-3 py-1.5 text-base font-bold leading-snug shadow-sm md:text-lg"
                            :class="getStatusBadgeClasses(order)"
                        >
                            <template v-if="getStatusLabelParts(order)">
                                <span dir="rtl">{{ getStatusLabelParts(order)![0] }}</span>
                                <span aria-hidden="true"> · </span>
                                <span dir="ltr">{{ getStatusLabelParts(order)![1] }}</span>
                            </template>
                            <template v-else>{{ order.shipping_status ?? order.status }}</template>
                        </p>
                    </div>

                    <!-- Items -->
                    <div class="flex-1 px-5 py-4">
                        <p v-if="order.delivery_name" class="mb-3 text-xl font-bold text-gray-900">
                            {{ order.delivery_name }}
                        </p>
                        <ul class="space-y-3">
                            <li
                                v-for="item in order.order_items"
                                :key="item.id"
                                class="rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3"
                            >
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="text-2xl font-bold text-gray-900">{{ item.item_name }}</span>
                                    <span
                                        class="shrink-0 rounded-full bg-gray-900 px-4 py-1 text-xl font-black text-white"
                                        dir="ltr"
                                    >
                                        ×{{ item.quantity }}
                                    </span>
                                </div>
                                <p
                                    v-if="item.special_instructions"
                                    class="mt-2 text-lg font-semibold text-orange-700"
                                >
                                    {{ item.special_instructions }}
                                </p>
                            </li>
                        </ul>
                        <p
                            v-if="order.special_instructions"
                            class="mt-4 rounded-xl border-2 border-orange-300 bg-orange-50 px-4 py-3 text-lg font-bold text-orange-800"
                        >
                            {{ tBoth('ملاحظة', 'Note') }}: {{ order.special_instructions }}
                        </p>
                    </div>

                    <!-- Action -->
                    <div class="border-t-2 border-gray-200 p-4">
                        <button
                            v-if="getNextAction(order)"
                            type="button"
                            :disabled="processingOrderId === order.id"
                            :class="[
                                'w-full rounded-2xl px-3 py-5 text-xl font-black leading-tight text-white shadow-md transition disabled:opacity-60 md:text-2xl',
                                getActionButtonClass(order),
                            ]"
                            @click="handleAction(order)"
                        >
                            <span v-if="processingOrderId === order.id">{{ t('جاري...', '...') }}</span>
                            <span v-else>{{ getActionLabel(order) }}</span>
                        </button>
                        <p v-else class="py-3 text-center text-lg font-semibold text-gray-500">
                            {{ tBoth('بانتظار التوصيل', 'Waiting for delivery') }}
                        </p>
                    </div>
                </article>
            </div>

            <div v-else class="mx-auto flex max-w-lg flex-col items-center justify-center py-24 text-center">
                <ChefHat class="h-24 w-24 text-gray-300" />
                <h2 class="mt-6 text-2xl font-bold text-gray-700 md:text-3xl">
                    {{ tBoth('لا توجد طلبات نشطة', 'No active orders') }}
                </h2>
                <p class="mt-2 text-lg text-gray-500 md:text-xl">
                    {{ tBoth('ستظهر الطلبات الجديدة هنا تلقائياً', 'New orders will appear here automatically') }}
                </p>
            </div>
        </main>
    </div>
</template>
