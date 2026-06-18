<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BarChart3 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'التقارير', href: '/reports' },
];
</script>

<template>
    <Head title="التقارير" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6" dir="rtl">

            <!-- كارد تقرير المبيعات -->
            <div class="flex justify-start">
                <a
                    href="/reports/sales"
                    class="group w-64 rounded-2xl border bg-white shadow-sm transition-all duration-200 overflow-hidden text-right dark:bg-gray-800 border-gray-200 hover:border-emerald-300 hover:shadow-md dark:border-gray-700"
                >
                    <div class="h-1.5 w-full bg-gradient-to-r from-gray-200 to-gray-300 group-hover:from-emerald-400 group-hover:to-teal-500 transition-all duration-300 dark:from-gray-600 dark:to-gray-500" />
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">تقرير</span>
                            <div class="rounded-lg bg-gray-100 group-hover:bg-emerald-50 p-1.5 transition-colors duration-200 dark:bg-gray-700">
                                <BarChart3 class="h-4 w-4 text-gray-400 group-hover:text-emerald-500 transition-colors duration-200" />
                            </div>
                        </div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">تقرير المبيعات</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">اضغط لعرض المعاملات المالية</p>
                    </div>
                </a>
            </div>

        </div>
    </AppLayout>
</template>
