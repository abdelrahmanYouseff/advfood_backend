<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, FileText, User, Store, DollarSign, Calendar, CreditCard, Hash, Search, Filter, X, Eye, TrendingUp } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface Props {
    invoices: Array<{
        id: number;
        invoice_number: string;
        order_reference?: string;
        status: string;
        total: number;
        subtotal?: number;
        delivery_fee?: number;
        tax?: number;
        due_date: string;
        created_at: string;
        paid_at?: string;
        user: {
            name: string;
            email: string;
        };
        restaurant: {
            name: string;
        };
        order: {
            order_number: string;
        };
    }>;
    filters?: {
        search?: string;
        date_from?: string;
        date_to?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

// Invoices page language (synced with sidebarLang)
const invoicesLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        invoicesLang.value = storedLang;
    }
}

onMounted(() => {
    if (typeof window === 'undefined') return;

    const handler = (event: Event) => {
        const lang = (event as CustomEvent).detail;
        if (lang === 'en' || lang === 'ar') {
            invoicesLang.value = lang;
        }
    };

    window.addEventListener('sidebar-lang-changed', handler as EventListener);

    onUnmounted(() => {
        window.removeEventListener('sidebar-lang-changed', handler as EventListener);
    });
});

const t = (ar: string, en: string) => (invoicesLang.value === 'ar' ? ar : en);

// Filter state
const searchQuery = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const statusFilter = ref(props.filters?.status || 'all');

// Statistics
const totalInvoices = computed(() => props.invoices.length);
const totalAmount = computed(() => props.invoices.reduce((sum, inv) => sum + inv.total, 0));
const paidInvoices = computed(() => props.invoices.filter(inv => inv.status === 'paid').length);
const pendingInvoices = computed(() => props.invoices.filter(inv => inv.status === 'pending').length);

// Apply filters
const applyFilters = () => {
    router.get(route('invoices.index'), {
        search: searchQuery.value || null,
        date_from: dateFrom.value || null,
        date_to: dateTo.value || null,
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
    }, {
        preserveState: true,
        // @ts-expect-error preserveScroll exists at runtime but is missing from TS types
        preserveScroll: true,
    });
};

// Clear filters
const clearFilters = () => {
    searchQuery.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    statusFilter.value = 'all';
    router.get(route('invoices.index'), {}, {
        preserveState: true,
        // @ts-expect-error preserveScroll exists at runtime but is missing from TS types
        preserveScroll: true,
    });
};

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return searchQuery.value || dateFrom.value || dateTo.value || statusFilter.value !== 'all';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('لوحة التحكم', 'Dashboard'),
        href: '/dashboard',
    },
    {
        title: t('الفواتير', 'Invoices'),
        href: '/invoices',
    },
];

const getStatusColor = (status: string) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        overdue: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    };
    return colors[status as keyof typeof colors] || colors.pending;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};

const formatDate = (date: string) => {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getStatusText = (status: string) => {
    if (invoicesLang.value === 'en') {
        const statusMapEn: Record<string, string> = {
            pending: 'Pending',
            paid: 'Paid',
            overdue: 'Overdue',
            cancelled: 'Cancelled',
        };
        return statusMapEn[status] ?? status;
    }

    const statusMapAr: Record<string, string> = {
        pending: 'معلق',
        paid: 'مدفوع',
        overdue: 'متأخر',
        cancelled: 'ملغي',
    };
    return statusMapAr[status] ?? status;
};
</script>

<template>
    <Head :title="t('الفواتير', 'Invoices')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ t('الفواتير', 'Invoices') }}
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ t('إدارة جميع فواتير العملاء', 'Manage all customer invoices') }}
                    </p>
                </div>
                <Link
                    :href="route('invoices.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    {{ t('إنشاء فاتورة', 'Create invoice') }}
                </Link>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <FileText class="h-5 w-5 text-blue-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ t('إجمالي الفواتير', 'Total invoices') }}
                            </p>
                            <p class="text-2xl font-bold">{{ totalInvoices }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <DollarSign class="h-5 w-5 text-green-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ t('إجمالي المبلغ', 'Total amount') }}
                            </p>
                            <p class="text-2xl font-bold">{{ formatCurrency(totalAmount) }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5 text-emerald-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ t('مدفوعة', 'Paid') }}
                            </p>
                            <p class="text-2xl font-bold">{{ paidInvoices }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <Calendar class="h-5 w-5 text-yellow-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">
                                {{ t('معلقة', 'Pending') }}
                            </p>
                            <p class="text-2xl font-bold">{{ pendingInvoices }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-lg border bg-card p-4 space-y-4">
                    <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Filter class="h-5 w-5 text-muted-foreground" />
                        <h3 class="font-semibold text-lg">
                            {{ t('الفلاتر والبحث', 'Filters & search') }}
                        </h3>
                    </div>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                    >
                        <X class="h-4 w-4" />
                        {{ t('مسح الفلاتر', 'Clear filters') }}
                    </button>
                </div>

                <div class="flex flex-wrap items-end gap-4">
                    <!-- Search -->
                    <div class="relative flex-1 min-w-[200px]">
                        <Search class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            :placeholder="t('البحث برقم الفاتورة، اسم العميل، المطعم...', 'Search by invoice number, customer name, restaurant...')"
                            class="w-full pr-10 rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                    </div>

                    <!-- Date From -->
                    <div class="min-w-[150px]">
                        <label class="block text-sm font-medium mb-1">
                            {{ t('من تاريخ', 'From date') }}
                        </label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="min-w-[150px]">
                        <label class="block text-sm font-medium mb-1">
                            {{ t('إلى تاريخ', 'To date') }}
                        </label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div class="min-w-[150px]">
                        <label class="block text-sm font-medium mb-1">
                            {{ t('الحالة', 'Status') }}
                        </label>
                        <select
                            v-model="statusFilter"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="all">
                                {{ t('جميع الحالات', 'All statuses') }}
                            </option>
                            <option value="pending">
                                {{ t('معلق', 'Pending') }}
                            </option>
                            <option value="paid">
                                {{ t('مدفوع', 'Paid') }}
                            </option>
                            <option value="overdue">
                                {{ t('متأخر', 'Overdue') }}
                            </option>
                            <option value="cancelled">
                                {{ t('ملغي', 'Cancelled') }}
                            </option>
                        </select>
                    </div>

                    <!-- Apply Button -->
                    <div>
                        <button
                            @click="applyFilters"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors h-[42px]"
                        >
                            <Search class="h-4 w-4" />
                            {{ t('تطبيق الفلاتر', 'Apply filters') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="rounded-lg border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('رقم الفاتورة', 'Invoice #') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('العميل', 'Customer') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('المطعم', 'Restaurant') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('رقم الطلب', 'Order #') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('المبلغ', 'Amount') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('الحالة', 'Status') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('تاريخ الإنشاء', 'Created at') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('تاريخ الاستحقاق', 'Due date') }}
                                </th>
                                <th class="h-10 px-3 text-right align-middle font-semibold text-xs">
                                    {{ t('الإجراءات', 'Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <div class="rounded-lg bg-blue-100 p-1.5 dark:bg-blue-900/20">
                                            <FileText class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                            <div class="font-medium text-xs">{{ invoice.invoice_number }}</div>
                                            <div v-if="invoice.order_reference" class="text-[10px] text-muted-foreground">
                                                Ref: {{ invoice.order_reference }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <User class="h-3 w-3 text-muted-foreground" />
                                        <div>
                                            <div class="font-medium text-xs">{{ invoice.user.name }}</div>
                                            <div class="text-[10px] text-muted-foreground">{{ invoice.user.email }}</div>
                                </div>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <Store class="h-3 w-3 text-muted-foreground" />
                                        <span class="font-medium text-xs">{{ invoice.restaurant.name }}</span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <CreditCard class="h-3 w-3 text-muted-foreground" />
                                        <span class="font-medium text-xs">{{ invoice.order.order_number }}</span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="text-right">
                                        <div class="font-bold text-sm text-green-600">{{ formatCurrency(invoice.total) }}</div>
                                        <div v-if="invoice.subtotal" class="text-[10px] text-muted-foreground">
                                            {{ t('فرعي:', 'Subtotal:') }} {{ formatCurrency(invoice.subtotal) }}
                                </div>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium', getStatusColor(invoice.status)]">
                                        {{ getStatusText(invoice.status) }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2 text-xs">
                                        <Calendar class="h-3 w-3 text-muted-foreground" />
                                        <span>{{ formatDate(invoice.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div v-if="invoice.due_date" class="flex items-center gap-2 text-xs">
                                        <Calendar class="h-3 w-3 text-muted-foreground" />
                                        <span>{{ formatDate(invoice.due_date) }}</span>
                                    </div>
                                    <span v-else class="text-muted-foreground text-xs">-</span>
                                </td>
                                <td class="p-3">
                                <Link
                                    :href="route('invoices.show', invoice.id)"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-2.5 py-1 text-[10px] font-medium text-white hover:bg-blue-700 transition-colors"
                                >
                                        <Eye class="h-3 w-3" />
                                    {{ t('عرض', 'View') }}
                                </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="invoices.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
                <FileText class="h-16 w-16 text-muted-foreground mb-4" />
                <h3 class="text-xl font-semibold mb-2">
                    {{ t('لا توجد فواتير', 'No invoices found') }}
                </h3>
                <p class="text-muted-foreground mb-4">
                    {{
                        hasActiveFilters
                            ? t('جرب تعديل معايير البحث أو الفلتر.', 'Try adjusting the search or filters.')
                            : t('ستظهر الفواتير هنا عند إنشائها', 'Invoices will appear here once created.')
                    }}
                </p>
                <Link
                    v-if="!hasActiveFilters"
                    :href="route('invoices.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    {{ t('إنشاء فاتورة', 'Create invoice') }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
