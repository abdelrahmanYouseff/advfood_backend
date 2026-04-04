<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, Printer, FileText, User, Store, Calendar, DollarSign, Phone } from 'lucide-vue-next';

interface OrderItemRow {
    id: number;
    item_name: string;
    quantity: number;
    price: number | string;
    subtotal: number | string;
    special_instructions?: string | null;
    menu_item?: {
        name?: string;
        description?: string | null;
        image?: string | null;
        preparation_time?: number | null;
    } | null;
}

interface Props {
    invoice: {
        id: number;
        invoice_number: string;
        order_reference?: string;
        status: string;
        subtotal: number;
        delivery_fee: number;
        tax: number;
        total: number;
        due_date: string;
        created_at: string;
        paid_at: string;
        notes: string;
        user: {
            name: string;
            email: string;
        };
        restaurant: {
            name: string;
            address: string;
            phone: string;
        };
        order: {
            order_number: string;
            delivery_address: string;
            delivery_phone: string;
            delivery_name: string;
            /** Laravel / Inertia: عادةً `order_items` */
            order_items?: OrderItemRow[];
            orderItems?: OrderItemRow[];
        };
    };
}

const props = defineProps<Props>();

/** عرض ثابت لرسوم التوصيل؛ الإجمالي = مجموع بنود الطلب + هذا المبلغ */
const INVOICE_FIXED_DELIVERY_FEE_SAR = 18;

const displayDeliveryFeeSar = INVOICE_FIXED_DELIVERY_FEE_SAR;

const invoiceLineItems = computed((): OrderItemRow[] => {
    const o = props.invoice.order;
    if (!o) return [];
    const raw = o.order_items ?? o.orderItems;
    return Array.isArray(raw) ? raw : [];
});

/** مجموع أعمدة المجموع لبنود الطلب؛ لو لا توجد بنود نستخدم subtotal المحفوظ في الفاتورة */
const lineItemsSubtotalSar = computed(() => {
    const items = invoiceLineItems.value;
    if (items.length === 0) {
        return Number(props.invoice.subtotal ?? 0);
    }
    return items.reduce((sum, item) => sum + Number(item.subtotal ?? 0), 0);
});

const displayInvoiceTotalSar = computed(() => {
    return lineItemsSubtotalSar.value + INVOICE_FIXED_DELIVERY_FEE_SAR;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Invoices',
        href: '/invoices',
    },
    {
        title: props.invoice.invoice_number,
        href: `/invoices/${props.invoice.id}`,
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

const getStatusText = (status: string) => {
    const statusMap = {
        pending: 'معلق',
        paid: 'مدفوع',
        overdue: 'متأخر',
        cancelled: 'ملغي',
    };
    return statusMap[status as keyof typeof statusMap] || status;
};

const formatCurrency = (amount: number | string) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(Number(amount ?? 0));
};

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('invoices.index')"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        العودة للفواتير
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">فاتورة {{ invoice.invoice_number }}</h1>
                        <p class="text-muted-foreground">تفاصيل الفاتورة والمدفوعات</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button
                        @click="printInvoice"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        <Printer class="h-4 w-4" />
                        طباعة
                    </button>
                    <Link
                        :href="route('invoices.edit', invoice.id)"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        تعديل
                    </Link>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Invoice Content -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <!-- Invoice Header -->
                        <div class="mb-6 flex items-center justify-between border-b pb-4">
                            <div class="flex items-center space-x-3">
                                <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                                    <FileText class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold">فاتورة {{ invoice.invoice_number }}</h2>
                                    <p class="text-sm text-muted-foreground">تاريخ الإنشاء: {{ new Date(invoice.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>
                            <span :class="['inline-flex rounded-full px-3 py-1 text-sm font-medium', getStatusColor(invoice.status)]">
                                {{ getStatusText(invoice.status) }}
                            </span>
                        </div>

                        <!-- Customer and Restaurant Info -->
                        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h3 class="mb-3 font-semibold">معلومات العميل</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <User class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ invoice.order.delivery_name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Phone class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ invoice.order.delivery_phone }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Store class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ invoice.order.delivery_address }}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="mb-3 font-semibold">معلومات المطعم</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <Store class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ invoice.restaurant.name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Calendar class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ invoice.restaurant.address }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ invoice.restaurant.phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order line items (customer purchases) -->
                        <div class="mb-6">
                            <h3 class="mb-3 font-semibold">عناصر الطلب (مشتريات العميل)</h3>
                            <div v-if="invoiceLineItems.length === 0" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                                لا توجد بنود مسجّلة لهذا الطلب.
                            </div>
                            <div v-else class="overflow-x-auto rounded-lg border">
                                <table class="w-full min-w-[640px]">
                                    <thead class="border-b bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3 text-right text-sm font-medium">الصنف والتفاصيل</th>
                                            <th class="px-4 py-3 text-center text-sm font-medium">الكمية</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium">سعر الوحدة</th>
                                            <th class="px-4 py-3 text-left text-sm font-medium">المجموع</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="item in invoiceLineItems" :key="item.id">
                                            <td class="px-4 py-3 text-sm">
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ item.item_name }}</div>
                                                <p
                                                    v-if="item.menu_item?.description"
                                                    class="mt-1 text-xs leading-relaxed text-muted-foreground"
                                                >
                                                    {{ item.menu_item.description }}
                                                </p>
                                                <p
                                                    v-if="item.special_instructions"
                                                    class="mt-1 text-xs text-amber-800 dark:text-amber-200"
                                                >
                                                    ملاحظة: {{ item.special_instructions }}
                                                </p>
                                                <p
                                                    v-if="item.menu_item?.preparation_time != null && item.menu_item.preparation_time > 0"
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    وقت التحضير: {{ item.menu_item.preparation_time }} دقيقة
                                                </p>
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-medium">{{ item.quantity }}</td>
                                            <td class="px-4 py-3 text-left text-sm">{{ formatCurrency(item.price) }}</td>
                                            <td class="px-4 py-3 text-left text-sm font-semibold">{{ formatCurrency(item.subtotal) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Invoice Summary -->
                        <div class="rounded-lg border bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">مجموع العناصر:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(lineItemsSubtotalSar) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">رسوم التوصيل:</span>
                                    <span class="text-sm font-medium">{{ formatCurrency(displayDeliveryFeeSar) }}</span>
                                </div>
                                <div class="border-t pt-2">
                                    <div class="flex justify-between">
                                        <span class="font-semibold">المجموع الكلي:</span>
                                        <span class="text-lg font-bold text-green-600">{{ formatCurrency(displayInvoiceTotalSar) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="invoice.notes" class="mt-6">
                            <h3 class="mb-2 font-semibold">ملاحظات:</h3>
                            <p class="text-sm text-muted-foreground">{{ invoice.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Payment Status -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold">حالة الدفع</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">الحالة:</span>
                                <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusColor(invoice.status)]">
                                    {{ getStatusText(invoice.status) }}
                                </span>
                            </div>
                            <div v-if="invoice.due_date" class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">تاريخ الاستحقاق:</span>
                                <span class="text-sm">{{ new Date(invoice.due_date).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="invoice.paid_at" class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">تاريخ الدفع:</span>
                                <span class="text-sm">{{ new Date(invoice.paid_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold">معلومات الطلب</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">رقم الطلب:</span>
                                <span class="text-sm font-medium">{{ invoice.order.order_number }}</span>
                            </div>
                            <div v-if="invoice.order_reference" class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Order Reference:</span>
                                <span class="text-sm font-medium text-blue-600">{{ invoice.order_reference }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">اسم المستلم:</span>
                                <span class="text-sm">{{ invoice.order.delivery_name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">هاتف التوصيل:</span>
                                <span class="text-sm">{{ invoice.order.delivery_phone }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
