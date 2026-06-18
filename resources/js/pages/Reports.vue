<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, CheckCircle2, XCircle, Clock, CreditCard, Store, User, Hash, Calendar, UserCheck } from 'lucide-vue-next';
import { ref } from 'vue';

interface Invoice {
    id: number;
    invoice_number: string;
    customer_name: string;
    restaurant: string;
    total: number;
    payment_method: string;
    order_status: string;
    date: string;
    is_collected: boolean;
    collected_at: string | null;
    collected_by: string | null;
}

const props = defineProps<{ invoices: Invoice[]; auth_role: string }>();

// Admin يقدر يغير الحالة في أي وقت — المحاسب لا يقدر بعد التحصيل
const canToggle = (invoice: Invoice) => {
    if (props.auth_role === 'admin') return true;
    return !invoice.is_collected;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'التقارير', href: '/reports' },
];

const showTable = ref(false);
const loadingId = ref<number | null>(null);

const toggleCollection = (invoice: Invoice) => {
    loadingId.value = invoice.id;
    router.post(`/reports/invoices/${invoice.id}/toggle-collection`, {}, {
        preserveScroll: true,
        onFinish: () => { loadingId.value = null; },
    });
};

const formatCurrency = (val: number) =>
    new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(val);

const paymentLabel = (method: string) => {
    const map: Record<string, string> = { online: 'إلكتروني', cash: 'نقدي', card: 'بطاقة' };
    return map[method] ?? method;
};

const paymentColor = (method: string) => {
    const map: Record<string, string> = {
        online: 'bg-blue-50 text-blue-600 border-blue-100',
        cash:   'bg-green-50 text-green-600 border-green-100',
        card:   'bg-purple-50 text-purple-600 border-purple-100',
    };
    return map[method] ?? 'bg-gray-50 text-gray-600 border-gray-100';
};

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        pending: 'معلق', confirmed: 'مؤكد', preparing: 'قيد التحضير',
        ready: 'جاهز', delivering: 'جاري التوصيل', delivered: 'مُسلَّم', cancelled: 'ملغي',
    };
    return map[status] ?? status;
};

const statusColor = (status: string) => {
    const map: Record<string, string> = {
        delivered:  'bg-emerald-50 text-emerald-600 border-emerald-100',
        confirmed:  'bg-blue-50 text-blue-600 border-blue-100',
        preparing:  'bg-orange-50 text-orange-600 border-orange-100',
        delivering: 'bg-indigo-50 text-indigo-600 border-indigo-100',
        cancelled:  'bg-red-50 text-red-600 border-red-100',
        pending:    'bg-yellow-50 text-yellow-600 border-yellow-100',
    };
    return map[status] ?? 'bg-gray-50 text-gray-600 border-gray-100';
};
</script>

<template>
    <Head title="التقارير" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6" dir="rtl">

            <!-- ── كارد تقرير المبيعات ─────────────────────────────── -->
            <div class="flex justify-start">
                <button
                    @click="showTable = !showTable"
                    class="group w-64 rounded-2xl border bg-white shadow-sm transition-all duration-200 overflow-hidden text-right dark:bg-gray-800"
                    :class="showTable
                        ? 'border-emerald-400 shadow-emerald-100 dark:border-emerald-600 dark:shadow-none'
                        : 'border-gray-200 hover:border-emerald-300 hover:shadow-md dark:border-gray-700'"
                >
                    <!-- شريط علوي ملوّن -->
                    <div class="h-1.5 w-full transition-all duration-300"
                        :class="showTable ? 'bg-gradient-to-r from-emerald-400 to-teal-500' : 'bg-gradient-to-r from-gray-200 to-gray-300 group-hover:from-emerald-300 group-hover:to-teal-400 dark:from-gray-600 dark:to-gray-500'"
                    />
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">تقرير</span>
                            <div class="rounded-lg p-1.5 transition-colors duration-200"
                                :class="showTable ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-gray-100 group-hover:bg-emerald-50 dark:bg-gray-700'"
                            >
                                <BarChart3 class="h-4 w-4 transition-colors duration-200"
                                    :class="showTable ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 group-hover:text-emerald-500'"
                                />
                            </div>
                        </div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">تقرير المبيعات</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            {{ showTable ? 'اضغط لإخفاء الجدول' : 'اضغط لعرض المعاملات المالية' }}
                        </p>
                    </div>
                </button>
            </div>

            <!-- ── الجدول ──────────────────────────────────────────── -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-3"
            >
                <div v-if="showTable" class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">

                    <!-- رأس الجدول -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="rounded-lg bg-emerald-50 p-1.5 dark:bg-emerald-900/30">
                                <BarChart3 class="h-4 w-4 text-emerald-500" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-800 dark:text-white">تقرير المبيعات</h2>
                                <p class="text-xs text-gray-400 dark:text-gray-500">المعاملات المالية المكتملة</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- ملخص سريع -->
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                                {{ invoices.filter(i => i.is_collected).length }} محصّلة
                            </span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                {{ invoices.filter(i => !i.is_collected).length }} غير محصّلة
                            </span>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                {{ invoices.length }} إجمالي
                            </span>
                        </div>
                    </div>

                    <!-- الجدول -->
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
                                        الحالة
                                    </th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5"><CreditCard class="h-3.5 w-3.5" /> طريقة الدفع</div>
                                    </th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        ترحيل
                                    </th>
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
                                    <td colspan="9" class="py-16 text-center text-sm text-gray-400 dark:text-gray-500">
                                        لا توجد معاملات مالية مكتملة حتى الآن
                                    </td>
                                </tr>
                                <tr
                                    v-for="invoice in invoices"
                                    :key="invoice.id"
                                    class="border-t border-gray-50 dark:border-gray-700/50 transition-colors duration-150"
                                    :class="invoice.is_collected
                                        ? 'bg-emerald-50/40 hover:bg-emerald-50/70 dark:bg-emerald-900/10 dark:hover:bg-emerald-900/20'
                                        : 'hover:bg-gray-50/80 dark:hover:bg-gray-700/20'"
                                >
                                    <!-- اسم العميل -->
                                    <td class="px-5 py-3.5">
                                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ invoice.customer_name }}</span>
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
                                            {{ formatCurrency(invoice.total) }}
                                        </span>
                                    </td>

                                    <!-- المطعم -->
                                    <td class="px-5 py-3.5">
                                        <span class="text-gray-700 dark:text-gray-200">{{ invoice.restaurant }}</span>
                                    </td>

                                    <!-- الحالة -->
                                    <td class="px-5 py-3.5">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors duration-200',
                                                invoice.is_collected
                                                    ? 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-700'
                                                    : 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-700'
                                            ]"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full"
                                                :class="invoice.is_collected ? 'bg-emerald-500' : 'bg-amber-500'"
                                            />
                                            {{ invoice.is_collected ? 'محصّلة' : 'غير محصّلة' }}
                                        </span>
                                    </td>

                                    <!-- طريقة الدفع -->
                                    <td class="px-5 py-3.5">
                                        <span :class="['inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium', paymentColor(invoice.payment_method)]">
                                            {{ paymentLabel(invoice.payment_method) }}
                                        </span>
                                    </td>

                                    <!-- ترحيل -->
                                    <td class="px-5 py-3.5 text-center">

                                        <!-- محصّلة + Admin (يقدر يغير) -->
                                        <button
                                            v-if="invoice.is_collected && auth_role === 'admin'"
                                            @click="toggleCollection(invoice)"
                                            :disabled="loadingId === invoice.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold border transition-all duration-150 bg-emerald-500 text-white border-emerald-500 hover:bg-emerald-600 disabled:opacity-60"
                                            title="أنت أدمن — يمكنك التراجع"
                                        >
                                            <span v-if="loadingId === invoice.id" class="h-3 w-3 rounded-full border-2 border-current border-t-transparent animate-spin" />
                                            <CheckCircle2 v-else class="h-3.5 w-3.5" />
                                            نعم
                                        </button>

                                        <!-- محصّلة + محاسب (مقفولة) -->
                                        <span
                                            v-else-if="invoice.is_collected"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold border bg-emerald-500 text-white border-emerald-500 cursor-not-allowed select-none"
                                            title="تم اعتماد التحصيل ولا يمكن التراجع"
                                        >
                                            <CheckCircle2 class="h-3.5 w-3.5" />
                                            نعم
                                        </span>

                                        <!-- لم يتم التحصيل بعد (الكل يقدر يضغط) -->
                                        <button
                                            v-else
                                            @click="toggleCollection(invoice)"
                                            :disabled="loadingId === invoice.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold border transition-all duration-150 bg-white text-gray-500 border-gray-200 hover:border-emerald-400 hover:text-emerald-600 hover:bg-emerald-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 disabled:opacity-60"
                                        >
                                            <span v-if="loadingId === invoice.id" class="h-3 w-3 rounded-full border-2 border-current border-t-transparent animate-spin" />
                                            <XCircle v-else class="h-3.5 w-3.5" />
                                            لا
                                        </button>

                                    </td>

                                    <!-- التاريخ -->
                                    <td class="px-5 py-3.5">
                                        <div class="text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                            {{ invoice.date }}
                                        </div>
                                        <!-- تاريخ التحصيل -->
                                        <div v-if="invoice.collected_at" class="mt-0.5 flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                            <Clock class="h-3 w-3" />
                                            تحصيل: {{ invoice.collected_at }}
                                        </div>
                                    </td>

                                    <!-- اسم الموظف -->
                                    <td class="px-5 py-3.5">
                                        <div v-if="invoice.collected_by" class="flex items-center gap-1.5">
                                            <div class="h-6 w-6 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                                                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                                    {{ invoice.collected_by.charAt(0) }}
                                                </span>
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
                                {{ formatCurrency(invoices.filter(i => i.is_collected).reduce((s, i) => s + i.total, 0)) }}
                            </span>
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            غير محصّلة:
                            <span class="font-semibold text-amber-600 dark:text-amber-400">
                                {{ formatCurrency(invoices.filter(i => !i.is_collected).reduce((s, i) => s + i.total, 0)) }}
                            </span>
                        </p>
                    </div>
                </div>
            </Transition>

        </div>
    </AppLayout>
</template>
