<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Menu, Store, Tag, DollarSign, Clock, Star } from 'lucide-vue-next';

interface Props {
    menuItems: Array<{
        id: number;
        name: string;
        description: string;
        price: number;
        image: string;
        is_available: boolean;
        is_featured: boolean;
        preparation_time: number;
        restaurant: {
            name: string;
        };
        category: {
            name: string;
        };
    }>;
    restaurants: Array<{
        id: number;
        name: string;
    }>;
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
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};
</script>

<template>
    <Head title="Menu Items" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">عناصر القائمة</h1>
                    <p class="text-muted-foreground">إدارة قوائم الطعام للمطاعم</p>
                </div>
                <Link
                    :href="route('menu-items.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إضافة منتج
                </Link>
            </div>

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="item in menuItems" :key="item.id" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                    <!-- Product Image -->
                    <div class="relative h-48 bg-gray-100 dark:bg-gray-800">
                        <img
                            v-if="item.image"
                            :src="`/storage/${item.image}`"
                            :alt="item.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full items-center justify-center">
                            <Menu class="h-12 w-12 text-gray-400" />
                        </div>
                        <!-- Status Badges -->
                        <div class="absolute top-3 right-3 flex space-x-1">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                    item.is_available
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                ]"
                            >
                                {{ item.is_available ? 'متاح' : 'غير متاح' }}
                            </span>
                            <span
                                v-if="item.is_featured"
                                class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                            >
                                مميز
                            </span>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ item.name }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{{ item.description }}</p>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                <Store class="h-4 w-4" />
                                <span>{{ item.restaurant.name }}</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                <Tag class="h-4 w-4" />
                                <span>{{ item.category.name }}</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                <Clock class="h-4 w-4" />
                                <span>{{ item.preparation_time }} دقيقة</span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <DollarSign class="h-4 w-4 text-green-600" />
                                <span class="text-lg font-semibold text-green-600">{{ formatCurrency(item.price) }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex space-x-2">
                            <Link
                                :href="route('menu-items.edit', item.id)"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                تعديل
                            </Link>
                            <Link
                                :href="route('menu-items.show', item.id)"
                                class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-700"
                            >
                                عرض
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="menuItems.length === 0" class="flex flex-col items-center justify-center py-12">
                <Menu class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">لا توجد منتجات في القائمة</h3>
                <p class="mt-2 text-muted-foreground">ابدأ بإضافة منتجات جديدة للمطاعم</p>
                <Link
                    :href="route('menu-items.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إضافة منتج
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
