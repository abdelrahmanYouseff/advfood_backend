<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Menu, Store, Tag, DollarSign, Clock, Star, Edit } from 'lucide-vue-next';

interface Props {
    menuItem: {
        id: number;
        name: string;
        description: string;
        price: number;
        image: string;
        is_available: boolean;
        is_featured: boolean;
        preparation_time: number;
        ingredients: string;
        allergens: string;
        restaurant: {
            name: string;
            address: string;
            phone: string;
        };
        category: {
            name: string;
        };
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Menu Items',
        href: '/menu-items',
    },
    {
        title: props.menuItem.name,
        href: `/menu-items/${props.menuItem.id}`,
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};
</script>

<template>
    <Head :title="`${menuItem.name} - Menu Item`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('menu-items.index')"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        العودة للقائمة
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">{{ menuItem.name }}</h1>
                        <p class="text-muted-foreground">تفاصيل المنتج</p>
                    </div>
                </div>
                <Link
                    :href="route('menu-items.edit', menuItem.id)"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Edit class="h-4 w-4" />
                    تعديل
                </Link>
            </div>

            <!-- Menu Item Details -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                        <!-- Product Image -->
                        <div class="relative h-64 bg-gray-100 dark:bg-gray-800">
                            <img
                                v-if="menuItem.image"
                                :src="`/storage/${menuItem.image}`"
                                :alt="menuItem.name"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full items-center justify-center">
                                <Menu class="h-16 w-16 text-gray-400" />
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Product Header -->
                            <div class="mb-6 flex items-center justify-between border-b pb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="rounded-lg bg-orange-100 p-3 dark:bg-orange-900/20">
                                        <Menu class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold">{{ menuItem.name }}</h2>
                                        <div class="mt-1 flex space-x-2">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                                    menuItem.is_available
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                                        : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                                ]"
                                            >
                                                {{ menuItem.is_available ? 'متاح' : 'غير متاح' }}
                                            </span>
                                            <span
                                                v-if="menuItem.is_featured"
                                                class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                                            >
                                                مميز
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center space-x-2">
                                        <DollarSign class="h-5 w-5 text-green-600" />
                                        <span class="text-2xl font-bold text-green-600">{{ formatCurrency(menuItem.price) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <h3 class="mb-2 font-semibold">وصف المنتج</h3>
                                <p class="text-muted-foreground">{{ menuItem.description || 'لا يوجد وصف متاح' }}</p>
                            </div>

                            <!-- Restaurant and Category Info -->
                            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <h3 class="mb-3 font-semibold">معلومات المطعم</h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <Store class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ menuItem.restaurant.name }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ menuItem.restaurant.address }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <DollarSign class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ menuItem.restaurant.phone }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="mb-3 font-semibold">معلومات الفئة</h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <Tag class="h-4 w-4 text-muted-foreground" />
                                            <span>{{ menuItem.category.name }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <span>وقت التحضير: {{ menuItem.preparation_time }} دقيقة</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ingredients -->
                            <div v-if="menuItem.ingredients" class="mb-6">
                                <h3 class="mb-2 font-semibold">المكونات</h3>
                                <div class="rounded-lg border bg-gray-50 p-4 dark:bg-gray-800">
                                    <p class="text-sm text-muted-foreground">{{ menuItem.ingredients }}</p>
                                </div>
                            </div>

                            <!-- Allergens -->
                            <div v-if="menuItem.allergens" class="mb-6">
                                <h3 class="mb-2 font-semibold">مسببات الحساسية</h3>
                                <div class="rounded-lg border bg-red-50 p-4 dark:bg-red-900/20">
                                    <p class="text-sm text-red-700 dark:text-red-400">{{ menuItem.allergens }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold">إجراءات سريعة</h3>
                        <div class="space-y-3">
                            <Link
                                :href="route('menu-items.edit', menuItem.id)"
                                class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                <Edit class="mr-2 h-4 w-4" />
                                تعديل المنتج
                            </Link>
                            <button
                                class="flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Star class="mr-2 h-4 w-4" />
                                {{ menuItem.is_featured ? 'إلغاء التميز' : 'تحديد كمميز' }}
                            </button>
                        </div>
                    </div>

                    <!-- Product Stats -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold">إحصائيات المنتج</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">الحالة:</span>
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                        menuItem.is_available
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                    ]"
                                >
                                    {{ menuItem.is_available ? 'متاح' : 'غير متاح' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">السعر:</span>
                                <span class="text-sm font-medium">{{ formatCurrency(menuItem.price) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">وقت التحضير:</span>
                                <span class="text-sm">{{ menuItem.preparation_time }} دقيقة</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">التميز:</span>
                                <span class="text-sm">{{ menuItem.is_featured ? 'نعم' : 'لا' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
