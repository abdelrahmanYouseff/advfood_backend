<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';

interface Customer {
    id: number;
    full_name: string;
    phone_number: string;
    street?: string;
    building_no?: string;
    floor?: string;
    apartment_number?: string;
    note?: string;
    source?: string;
    latest_status?: string;
    restaurant?: string;
    created_at?: string;
    order_id?: number;
    link_order_id?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface CustomersPagination {
    data: Customer[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
}

interface Props {
    customers: CustomersPagination;
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

// Online customers language (synced with sidebarLang)
const customersLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        customersLang.value = storedLang;
    }
}

onMounted(() => {
    if (typeof window === 'undefined') return;

    const handler = (event: Event) => {
        const lang = (event as CustomEvent).detail;
        if (lang === 'en' || lang === 'ar') {
            customersLang.value = lang;
        }
    };

    window.addEventListener('sidebar-lang-changed', handler as EventListener);

    onUnmounted(() => {
        window.removeEventListener('sidebar-lang-changed', handler as EventListener);
    });
});

const t = (ar: string, en: string) => (customersLang.value === 'ar' ? ar : en);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('لوحة التحكم', 'Dashboard'),
        href: '/dashboard',
    },
    {
        title: t('العملاء', 'Customers'),
        href: '/online-customers',
    },
];

const search = ref(props.filters?.search ?? '');
const isSearching = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    isSearching.value = true;

    searchTimeout = setTimeout(() => {
        router.get(
            route('online-customers.index'),
            { search: value || undefined },
            {
                preserveState: true,
                replace: true,
                onFinish: () => {
                    isSearching.value = false;
                },
            }
        );
    }, 400);
});

const customers = computed(() => props.customers?.data ?? []);
const links = computed(() => props.customers?.links ?? []);
const totalCustomers = computed(() => props.customers?.meta?.total ?? customers.value.length);
const exportUrl = computed(() =>
    route('online-customers.export', {
        search: search.value || undefined,
    }),
);

const formatAddress = (customer: Customer) => {
    const buildingLabel = customersLang.value === 'ar' ? 'مبنى' : 'Building';
    const floorLabel = customersLang.value === 'ar' ? 'الدور' : 'Floor';
    const aptLabel = customersLang.value === 'ar' ? 'شقة' : 'Apartment';

    const parts = [
        customer.street,
        customer.building_no ? `${buildingLabel} ${customer.building_no}` : undefined,
        customer.floor ? `${floorLabel} ${customer.floor}` : undefined,
        customer.apartment_number ? `${aptLabel} ${customer.apartment_number}` : undefined,
    ].filter(Boolean);

    return parts.length ? parts.join(' - ') : '—';
};

const formatStatus = (status?: string) => {
    if (!status) return '—';

    if (customersLang.value === 'en') {
        const mapEn: Record<string, string> = {
            link_order_saved: 'Link order saved',
            order_created_pending_payment: 'Order created (pending payment)',
            webhook_paid: 'Paid (Webhook)',
        };
        return mapEn[status] ?? status;
    }

    const mapAr: Record<string, string> = {
        link_order_saved: 'تم حفظ طلب الرابط',
        order_created_pending_payment: 'تم إنشاء الطلب (بانتظار الدفع)',
        webhook_paid: 'تم الدفع (Webhook)',
    };

    return mapAr[status] ?? status;
};

const formatInteractionDate = (value?: string) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};

const formatSource = (source?: string) => {
    if (!source) return '—';

    if (customersLang.value === 'en') {
        const mapEn: Record<string, string> = {
            save_order: 'Save order',
            initiate_payment: 'Initiate payment',
            link: 'Link',
            application: 'Application',
            internal: 'Internal',
            web: 'Web',
        };
        return mapEn[source] ?? source;
    }

    const mapAr: Record<string, string> = {
        save_order: 'حفظ الطلب',
        initiate_payment: 'بدء الدفع',
        link: 'Link',
        application: 'Application',
        internal: 'Internal',
        web: 'Web',
    };
    return mapAr[source] ?? source;
};
</script>

<template>
    <Head :title="t('العملاء', 'Customers')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ t('العملاء', 'Customers') }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t('عرض العملاء المسجلين من صفحة الطلبات عبر الرابط الخارجي', 'View customers saved from the external order link page') }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        <span v-if="customersLang === 'ar'">
                            إجمالي العملاء: {{ totalCustomers }}
                        </span>
                        <span v-else>
                            Total customers: {{ totalCustomers }}
                        </span>
                    </p>
                </div>
                <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center md:gap-3">
                    <div class="relative md:w-72">
                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            :placeholder="t('ابحث بالاسم أو الرقم أو العنوان', 'Search by name, phone, or address')"
                        />
                        <span v-if="isSearching" class="absolute inset-y-0 left-3 flex items-center text-xs text-muted-foreground">
                            {{ t('جاري البحث...', 'Searching...') }}
                        </span>
                    </div>
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700"
                    >
                        {{ t('تنزيل Excel', 'Download Excel') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">
                                {{ t('الاسم', 'Name') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('رقم الهاتف', 'Phone number') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('المطعم', 'Restaurant') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('العنوان', 'Address') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('آخر تفاعل', 'Last interaction') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('الحالة الحالية', 'Current status') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('المصدر', 'Source') }}
                            </th>
                            <th class="px-4 py-3">
                                {{ t('معرّفات مرتبطة', 'Linked identifiers') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="!customers.length">
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                {{ t('لا توجد بيانات عملاء حتى الآن.', 'No customer data yet.') }}
                            </td>
                        </tr>
                        <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ customer.full_name }}</div>
                                <div v-if="customer.note" class="text-xs text-muted-foreground">{{ customer.note }}</div>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ customer.phone_number }}</td>
                            <td class="px-4 py-3">{{ customer.restaurant ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ formatAddress(customer) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">
                                    {{ formatInteractionDate(customer.created_at) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ formatStatus(customer.latest_status) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-900">{{ formatSource(customer.source) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="space-y-1 text-xs text-muted-foreground">
                                    <div>
                                        <span class="font-medium">
                                            {{ t('رقم الطلب:', 'Order ID:') }}
                                        </span>
                                        <template v-if="customer.order_id">
                                            <Link :href="route('orders.show', customer.order_id)" class="text-blue-600 hover:underline">
                                                #{{ customer.order_id }}
                                            </Link>
                                        </template>
                                        <template v-else>
                                            —
                                        </template>
                                    </div>
                                    <div>
                                        <span class="font-medium">
                                            {{ t('طلب الرابط:', 'Link order:') }}
                                        </span>
                                        <template v-if="customer.link_order_id">
                                            <Link :href="route('link-orders.show', customer.link_order_id)" class="text-blue-600 hover:underline">
                                                #{{ customer.link_order_id }}
                                            </Link>
                                        </template>
                                        <template v-else>
                                            —
                                        </template>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="links.length > 1" class="flex flex-wrap items-center justify-end gap-2">
                <Link
                    v-for="link in links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md px-3 py-1 text-sm"
                    :class="[
                        link.active
                            ? 'bg-blue-600 text-white'
                            : link.url
                                ? 'bg-white text-gray-700 hover:bg-gray-100'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>


