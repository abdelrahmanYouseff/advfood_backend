<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, FileText, User, Store, DollarSign, Calendar, CreditCard, Hash } from 'lucide-vue-next';

interface Props {
    invoices: Array<{
        id: number;
        invoice_number: string;
        order_reference?: string;
        status: string;
        total: number;
        due_date: string;
        created_at: string;
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
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Invoices',
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
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};

const getStatusText = (status: string) => {
    const statusMap = {
        pending: 'معلق',
        paid: 'مدفوع',
        overdue: 'متأخر',
        cancelled: 'ملغي',
    };
    return statusMap[status as keyof typeof statusMap] || status;
};
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">الفواتير</h1>
                    <p class="text-muted-foreground">إدارة جميع فواتير العملاء</p>
                </div>
                <Link
                    :href="route('invoices.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إنشاء فاتورة
                </Link>
            </div>

            <!-- Invoices List -->
            <div class="space-y-4">
                <div v-for="invoice in invoices" :key="invoice.id" class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                                <FileText class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-semibold">{{ invoice.invoice_number }}</h3>
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusColor(invoice.status)]">
                                        {{ getStatusText(invoice.status) }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-muted-foreground">
                                    <div class="flex items-center space-x-1">
                                        <User class="h-4 w-4" />
                                        <span>{{ invoice.user.name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <Store class="h-4 w-4" />
                                        <span>{{ invoice.restaurant.name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <CreditCard class="h-4 w-4" />
                                        <span>Order: {{ invoice.order.order_number }}</span>
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center space-x-4 text-sm text-muted-foreground">
                                    <div class="flex items-center space-x-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>Created: {{ new Date(invoice.created_at).toLocaleDateString() }}</span>
                                    </div>
                                    <div v-if="invoice.due_date" class="flex items-center space-x-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>Due: {{ new Date(invoice.due_date).toLocaleDateString() }}</span>
                                    </div>
                                    <div v-if="invoice.order_reference" class="flex items-center space-x-1">
                                        <Hash class="h-4 w-4" />
                                        <span>Order Ref: {{ invoice.order_reference }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-semibold text-green-600">{{ formatCurrency(invoice.total) }}</div>
                            <div class="mt-2 flex space-x-2">
                                <Link
                                    :href="route('invoices.show', invoice.id)"
                                    class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700"
                                >
                                    عرض
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="invoices.length === 0" class="flex flex-col items-center justify-center py-12">
                <FileText class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">لا توجد فواتير</h3>
                <p class="mt-2 text-muted-foreground">ستظهر الفواتير هنا عند إنشائها</p>
                <Link
                    :href="route('invoices.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إنشاء فاتورة
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
