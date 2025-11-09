<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'لوحة التحكم',
        href: '/dashboard',
    },
    {
        title: 'العملاء',
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
    const parts = [
        customer.street,
        customer.building_no ? `مبنى ${customer.building_no}` : undefined,
        customer.floor ? `الدور ${customer.floor}` : undefined,
        customer.apartment_number ? `شقة ${customer.apartment_number}` : undefined,
    ].filter(Boolean);

    return parts.length ? parts.join(' - ') : '—';
};

const formatStatus = (status?: string) => {
    if (!status) return '—';
    const map: Record<string, string> = {
        link_order_saved: 'تم حفظ طلب الرابط',
        order_created_pending_payment: 'تم إنشاء الطلب (بانتظار الدفع)',
        webhook_paid: 'تم الدفع (Webhook)',
    };

    return map[status] ?? status;
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
    const map: Record<string, string> = {
        save_order: 'حفظ الطلب',
        initiate_payment: 'بدء الدفع',
        link: 'Link',
        application: 'Application',
        internal: 'Internal',
        web: 'Web',
    };
    return map[source] ?? source;
};
</script>

<template>
    <Head title="العملاء" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">العملاء</h1>
                    <p class="text-muted-foreground">عرض العملاء المسجلين من صفحة الطلبات عبر الرابط الخارجي</p>
                    <p class="mt-1 text-xs text-muted-foreground">إجمالي العملاء: {{ totalCustomers }}</p>
                </div>
                <div class="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center md:gap-3">
                    <div class="relative md:w-72">
                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="ابحث بالاسم أو الرقم أو العنوان"
                        />
                        <span v-if="isSearching" class="absolute inset-y-0 left-3 flex items-center text-xs text-muted-foreground">جاري البحث...</span>
                    </div>
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700"
                    >
                        تنزيل Excel
                    </a>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3">الاسم</th>
                            <th class="px-4 py-3">رقم الهاتف</th>
                            <th class="px-4 py-3">المطعم</th>
                            <th class="px-4 py-3">العنوان</th>
                            <th class="px-4 py-3">آخر تفاعل</th>
                            <th class="px-4 py-3">الحالة الحالية</th>
                            <th class="px-4 py-3">المصدر</th>
                            <th class="px-4 py-3">معرّفات مرتبطة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="!customers.length">
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-muted-foreground">لا توجد بيانات عملاء حتى الآن.</td>
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
                                        <span class="font-medium">Order ID:</span>
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
                                        <span class="font-medium">Link Order:</span>
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


