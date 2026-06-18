<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    BarChart3, CheckCircle2, XCircle, Clock, CreditCard, Store,
    User, Hash, Calendar, UserCheck, X, Phone, MapPin, Package,
    FileText, ChevronLeft, Download, ArrowRight
} from 'lucide-vue-next';
import { ref } from 'vue';

interface InvoiceItem { name: string; quantity: number; price: number; subtotal: number; }

interface Invoice {
    id: number;
    invoice_number: string;
    order_reference?: string;
    order_number?: string;
    customer_name: string;
    customer_phone?: string;
    customer_address?: string;
    restaurant: string;
    subtotal: number;
    delivery_fee: number;
    tax: number;
    total: number;
    payment_method: string;
    order_status: string;
    date: string;
    paid_at?: string;
    is_collected: boolean;
    collected_at: string | null;
    collected_by: string | null;
    items: InvoiceItem[];
}

const props = defineProps<{ invoices: Invoice[]; auth_role: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'التقارير', href: '/reports' },
    { title: 'تقرير المبيعات', href: '/reports/sales' },
];

const loadingId = ref<number | null>(null);
const drawer    = ref<Invoice | null>(null);

const openDrawer  = (inv: Invoice) => { drawer.value = inv; };
const closeDrawer = () => { drawer.value = null; };

const toggleCollection = (invoice: Invoice, e: Event) => {
    e.stopPropagation();
    loadingId.value = invoice.id;
    router.post(`/reports/invoices/${invoice.id}/toggle-collection`, {}, {
        preserveScroll: true,
        onFinish: () => { loadingId.value = null; },
    });
};

const fmt = (val: number) =>
    new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(val);

const paymentLabel = (m: string) =>
    ({ online: 'إلكتروني', cash: 'نقدي', card: 'بطاقة' }[m] ?? m);

const paymentColor = (m: string) =>
    ({ online: 'bg-blue-50 text-blue-600 border-blue-100',
       cash:   'bg-green-50 text-green-600 border-green-100',
       card:   'bg-purple-50 text-purple-600 border-purple-100' }[m]
     ?? 'bg-gray-50 text-gray-600 border-gray-100');

const statusLabel = (s: string) =>
    ({ pending: 'معلق', confirmed: 'مؤكد', preparing: 'قيد التحضير',
       ready: 'جاهز', delivering: 'جاري التوصيل', delivered: 'مُسلَّم', cancelled: 'ملغي' }[s] ?? s);

const statusColor = (s: string) =>
    ({ delivered:  'bg-emerald-50 text-emerald-600 border-emerald-100',
       confirmed:  'bg-blue-50 text-blue-600 border-blue-100',
       preparing:  'bg-orange-50 text-orange-600 border-orange-100',
       delivering: 'bg-indigo-50 text-indigo-600 border-indigo-100',
       cancelled:  'bg-red-50 text-red-600 border-red-100',
       pending:    'bg-yellow-50 text-yellow-600 border-yellow-100' }[s]
     ?? 'bg-gray-50 text-gray-600 border-gray-100');
</script>

<template>
    <Head title="تقرير المبيعات" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6" dir="rtl">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-emerald-50 p-2.5 dark:bg-emerald-900/30">
                        <BarChart3 class="h-6 w-6 text-emerald-500" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">تقرير المبيعات</h1>
                        <p class="text-xs text-gray-400 dark:text-gray-500">المعاملات المالية المكتملة — اضغط على أي سطر لعرض التفاصيل</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- زر تصدير Excel -->
                    <a
                        href="/reports/export-excel"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150"
                    >
                        <Download class="h-4 w-4" />
                        تصدير Excel
                    </a>

                    <!-- رجوع للتقارير -->
                    <a
                        href="/reports"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors duration-150"
                    >
                        <ArrowRight class="h-4 w-4" />
                        رجوع
                    </a>
                </div>
            </div>

            <!-- Stats bar -->
            <div class="flex items-center gap-3 flex-wrap">
                <span class="rounded-full bg-emerald-50 border border-emerald-100 px-4 py-1.5 text-sm font-medium text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                    {{ invoices.filter(i => i.is_collected).length }} محصّلة
                </span>
                <span class="rounded-full bg-amber-50 border border-amber-100 px-4 py-1.5 text-sm font-medium text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    {{ invoices.filter(i => !i.is_collected).length }} غير محصّلة
                </span>
                <span class="rounded-full bg-gray-100 border border-gray-200 px-4 py-1.5 text-sm font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    {{ invoices.length }} إجمالي
                </span>
                <span class="rounded-full bg-blue-50 border border-blue-100 px-4 py-1.5 text-sm font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 mr-auto">
                    إجمالي المحصّل: {{ fmt(invoices.filter(i => i.is_collected).reduce((s, i) => s + i.total, 0)) }}
                </span>
            </div>

            <!-- الجدول -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/40">
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><User class="h-3.5 w-3.5" /> اسم العميل</div>
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><Hash class="h-3.5 w-3.5" /> رقم الفاتورة</div>
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><span class="text-xs">﷼</span> المبلغ الإجمالي</div>
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><Store class="h-3.5 w-3.5" /> اسم المطعم</div>
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><CreditCard class="h-3.5 w-3.5" /> طريقة الدفع</div>
                                </th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">ترحيل</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><Calendar class="h-3.5 w-3.5" /> التاريخ</div>
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5"><UserCheck class="h-3.5 w-3.5" /> اسم الموظف</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="invoices.length === 0">
                                <td colspan="8" class="py-20 text-center text-sm text-gray-400 dark:text-gray-500">
                                    لا توجد معاملات مالية مكتملة حتى الآن
                                </td>
                            </tr>
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                @click="openDrawer(invoice)"
                                class="border-t border-gray-50 dark:border-gray-700/50 transition-colors duration-150 cursor-pointer"
                                :class="invoice.is_collected
                                    ? 'bg-emerald-50/40 hover:bg-emerald-100/60 dark:bg-emerald-900/10 dark:hover:bg-emerald-900/20'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                            >
                                <!-- اسم العميل -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ invoice.customer_name }}</span>
                                        <ChevronLeft class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" />
                                    </div>
                                </td>

                                <!-- رقم الفاتورة -->
                                <td class="px-5 py-3.5">
                                    <span class="font-mono text-xs font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">
                                        {{ invoice.invoice_number }}
                                    </span>
                                </td>

                                <!-- المبلغ -->
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        {{ fmt(invoice.total) }}
                                    </span>
                                </td>

                                <!-- المطعم -->
                                <td class="px-5 py-3.5 text-gray-700 dark:text-gray-200">{{ invoice.restaurant }}</td>

                                <!-- طريقة الدفع -->
                                <td class="px-5 py-3.5">
                                    <span :class="['inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium', paymentColor(invoice.payment_method)]">
                                        {{ paymentLabel(invoice.payment_method) }}
                                    </span>
                                </td>

                                <!-- ترحيل -->
                                <td class="px-5 py-3.5 text-center">
                                    <button
                                        v-if="invoice.is_collected && auth_role === 'admin'"
                                        @click="toggleCollection(invoice, $event)"
                                        :disabled="loadingId === invoice.id"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold border bg-emerald-500 text-white border-emerald-500 hover:bg-emerald-600 disabled:opacity-60 transition-all"
                                        title="أنت أدمن — يمكنك التراجع"
                                    >
                                        <span v-if="loadingId === invoice.id" class="h-3 w-3 rounded-full border-2 border-current border-t-transparent animate-spin" />
                                        <CheckCircle2 v-else class="h-3.5 w-3.5" /> نعم
                                    </button>
                                    <span
                                        v-else-if="invoice.is_collected"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold border bg-emerald-500 text-white border-emerald-500 cursor-not-allowed select-none"
                                        title="تم اعتماد التحصيل ولا يمكن التراجع"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" /> نعم
                                    </span>
                                    <button
                                        v-else
                                        @click="toggleCollection(invoice, $event)"
                                        :disabled="loadingId === invoice.id"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold border bg-white text-gray-500 border-gray-200 hover:border-emerald-400 hover:text-emerald-600 hover:bg-emerald-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 disabled:opacity-60 transition-all"
                                    >
                                        <span v-if="loadingId === invoice.id" class="h-3 w-3 rounded-full border-2 border-current border-t-transparent animate-spin" />
                                        <XCircle v-else class="h-3.5 w-3.5" /> لا
                                    </button>
                                </td>

                                <!-- التاريخ -->
                                <td class="px-5 py-3.5">
                                    <div class="text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ invoice.date }}</div>
                                    <div v-if="invoice.collected_at" class="mt-0.5 flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        <Clock class="h-3 w-3" /> تحصيل: {{ invoice.collected_at }}
                                    </div>
                                </td>

                                <!-- اسم الموظف -->
                                <td class="px-5 py-3.5">
                                    <div v-if="invoice.collected_by" class="flex items-center gap-1.5">
                                        <div class="h-6 w-6 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                                            <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">{{ invoice.collected_by.charAt(0) }}</span>
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ invoice.collected_by }}</span>
                                    </div>
                                    <span v-else class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20 flex items-center justify-between">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        إجمالي المبالغ المحصّلة:
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ fmt(invoices.filter(i => i.is_collected).reduce((s, i) => s + i.total, 0)) }}
                        </span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        غير محصّلة:
                        <span class="font-semibold text-amber-600 dark:text-amber-400">
                            {{ fmt(invoices.filter(i => !i.is_collected).reduce((s, i) => s + i.total, 0)) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- ══ Drawer تفاصيل الفاتورة ══ -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="drawer" @click="closeDrawer" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm" />
            </Transition>

            <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                <div v-if="drawer" class="fixed top-0 left-0 z-50 h-full w-full max-w-md bg-white dark:bg-gray-900 shadow-2xl overflow-y-auto" dir="rtl">

                    <!-- Header -->
                    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/30 p-2">
                                <FileText class="h-5 w-5 text-emerald-500" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">تفاصيل الفاتورة</p>
                                <p class="font-mono text-sm font-bold text-gray-800 dark:text-white">{{ drawer.invoice_number }}</p>
                            </div>
                        </div>
                        <button @click="closeDrawer" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 transition">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="p-5 space-y-5">
                        <!-- حالة التحصيل -->
                        <div :class="['flex items-center gap-2.5 rounded-xl border px-4 py-3',
                            drawer.is_collected
                                ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700'
                                : 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700']">
                            <div :class="['h-2.5 w-2.5 rounded-full', drawer.is_collected ? 'bg-emerald-500' : 'bg-amber-500']" />
                            <span :class="['text-sm font-semibold', drawer.is_collected ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400']">
                                {{ drawer.is_collected ? 'محصّلة' : 'غير محصّلة' }}
                            </span>
                            <span v-if="drawer.collected_at" class="mr-auto text-xs text-emerald-600 dark:text-emerald-400">{{ drawer.collected_at }}</span>
                        </div>

                        <!-- بيانات العميل -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 space-y-2.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">بيانات العميل</p>
                            <div class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200"><User class="h-4 w-4 text-gray-400 flex-shrink-0" />{{ drawer.customer_name }}</div>
                            <div v-if="drawer.customer_phone" class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-200"><Phone class="h-4 w-4 text-gray-400 flex-shrink-0" />{{ drawer.customer_phone }}</div>
                            <div v-if="drawer.customer_address" class="flex items-start gap-2.5 text-sm text-gray-700 dark:text-gray-200"><MapPin class="h-4 w-4 text-gray-400 flex-shrink-0 mt-0.5" />{{ drawer.customer_address }}</div>
                        </div>

                        <!-- بيانات الطلب -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4 space-y-2.5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">بيانات الطلب</p>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-500 dark:text-gray-400"><Store class="h-4 w-4" /> المطعم</span>
                                <span class="font-medium text-gray-800 dark:text-white">{{ drawer.restaurant }}</span>
                            </div>
                            <div v-if="drawer.order_number" class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-500 dark:text-gray-400"><Hash class="h-4 w-4" /> رقم الطلب</span>
                                <span class="font-mono text-xs font-semibold bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-700 dark:text-gray-200">{{ drawer.order_number }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-500 dark:text-gray-400"><CreditCard class="h-4 w-4" /> طريقة الدفع</span>
                                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', paymentColor(drawer.payment_method)]">{{ paymentLabel(drawer.payment_method) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-500 dark:text-gray-400"><Package class="h-4 w-4" /> حالة الطلب</span>
                                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', statusColor(drawer.order_status)]">{{ statusLabel(drawer.order_status) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-500 dark:text-gray-400"><Calendar class="h-4 w-4" /> التاريخ</span>
                                <span class="text-xs text-gray-700 dark:text-gray-200">{{ drawer.date }}</span>
                            </div>
                        </div>

                        <!-- عناصر الطلب -->
                        <div v-if="drawer.items.length" class="rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2.5 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">عناصر الطلب</p>
                            </div>
                            <div class="divide-y divide-gray-50 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                                <div v-for="(item, i) in drawer.items" :key="i" class="flex items-center justify-between px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-bold text-gray-500 dark:text-gray-300">{{ item.quantity }}</span>
                                        <span class="text-gray-700 dark:text-gray-200">{{ item.name }}</span>
                                    </div>
                                    <span class="font-semibold text-gray-800 dark:text-white">{{ fmt(item.subtotal) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- ملخص المبالغ -->
                        <div class="rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                            <div class="divide-y divide-gray-50 dark:divide-gray-700/60 px-4">
                                <div class="flex justify-between py-3 text-sm text-gray-600 dark:text-gray-300"><span>قيمة المنتجات</span><span>{{ fmt(drawer.subtotal) }}</span></div>
                                <div class="flex justify-between py-3 text-sm text-gray-600 dark:text-gray-300"><span>رسوم التوصيل</span><span>{{ fmt(drawer.delivery_fee) }}</span></div>
                                <div class="flex justify-between py-3 text-sm text-gray-600 dark:text-gray-300"><span>الضريبة</span><span>{{ fmt(drawer.tax) }}</span></div>
                                <div class="flex justify-between py-3 text-base font-bold text-emerald-600 dark:text-emerald-400"><span>الإجمالي</span><span>{{ fmt(drawer.total) }}</span></div>
                            </div>
                        </div>

                        <!-- موظف التحصيل -->
                        <div v-if="drawer.collected_by" class="flex items-center gap-3 rounded-xl border border-emerald-100 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-3">
                            <div class="h-9 w-9 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-300">{{ drawer.collected_by.charAt(0) }}</span>
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400">تم التحصيل بواسطة</p>
                                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ drawer.collected_by }}</p>
                                <p v-if="drawer.collected_at" class="text-xs text-emerald-500 dark:text-emerald-400">{{ drawer.collected_at }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AppLayout>
</template>
