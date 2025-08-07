<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Menu, Store, DollarSign, Clock, Star, Filter } from 'lucide-vue-next';
import { ref, computed } from 'vue';

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
            id: number;
            name: string;
        } | null;
    }>;
    restaurants: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

// Filter state
const selectedRestaurantId = ref('');

// Computed filtered items
const filteredMenuItems = computed(() => {
    if (!selectedRestaurantId.value) {
        return props.menuItems;
    }
    return props.menuItems.filter(item => 
        item.restaurant && item.restaurant.id === parseInt(selectedRestaurantId.value)
    );
});

// Get restaurant name by ID
const getRestaurantName = (id: number) => {
    const restaurant = props.restaurants.find(r => r.id === id);
    return restaurant ? restaurant.name : '';
};

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
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-purple-700 hover:shadow-xl hover:scale-105"
                >
                    <Plus class="h-4 w-4 transition-transform group-hover:rotate-90" />
                    إضافة منتج جديد
                </Link>
            </div>

            <!-- Filter Section -->
            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-blue-50 to-purple-50 p-4 dark:from-blue-900/20 dark:to-purple-900/20">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">فلتر المنتجات</span>
                    </div>
                    <div class="relative">
                        <select
                            v-model="selectedRestaurantId"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">جميع المطاعم</option>
                            <option v-for="restaurant in restaurants" :key="restaurant.id" :value="restaurant.id">
                                {{ restaurant.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span v-if="selectedRestaurantId">
                        {{ filteredMenuItems.length }} منتج في {{ getRestaurantName(parseInt(selectedRestaurantId)) }}
                    </span>
                    <span v-else>
                        {{ filteredMenuItems.length }} منتج إجمالي
                    </span>
                </div>
            </div>

            <!-- Menu Items Grid -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                <div v-for="item in filteredMenuItems" :key="item.id" class="group relative overflow-hidden rounded-2xl bg-white shadow-sm transition-all duration-300 hover:shadow-lg hover:scale-105 dark:bg-gray-800">
                    <!-- Product Image -->
                    <div class="relative aspect-square bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                        <img
                            v-if="item.image"
                            :src="`/storage/${item.image}`"
                            :alt="item.name"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                        />
                        <div v-else class="flex h-full items-center justify-center">
                            <Menu class="h-8 w-8 text-gray-400" />
                        </div>
                        
                        <!-- Status Badges -->
                        <div class="absolute top-2 right-2 flex flex-col space-y-1">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium shadow-sm',
                                    item.is_available
                                        ? 'bg-green-500 text-white'
                                        : 'bg-red-500 text-white'
                                ]"
                            >
                                {{ item.is_available ? 'متاح' : 'غير متاح' }}
                            </span>
                            <span
                                v-if="item.is_featured"
                                class="inline-flex items-center rounded-full bg-yellow-500 px-2 py-1 text-xs font-medium text-white shadow-sm"
                            >
                                مميز
                            </span>
                        </div>
                        
                        <!-- Price Badge -->
                        <div class="absolute bottom-2 left-2">
                            <div class="flex items-center space-x-1 rounded-full bg-white/90 px-3 py-1.5 text-sm font-bold text-green-600 shadow-sm backdrop-blur-sm dark:bg-gray-800/90 dark:text-green-400">
                                <DollarSign class="h-3 w-3" />
                                <span>{{ formatCurrency(item.price) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-3">
                        <div class="mb-2">
                            <h3 class="text-sm font-semibold text-gray-900 line-clamp-1 dark:text-white">{{ item.name }}</h3>
                            <p class="mt-1 text-xs text-gray-500 line-clamp-2 dark:text-gray-400">{{ item.description }}</p>
                        </div>

                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center space-x-1">
                                <Store class="h-3 w-3" />
                                <span class="line-clamp-1">{{ item.restaurant?.name || 'غير محدد' }}</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <Clock class="h-3 w-3" />
                                <span>{{ item.preparation_time }}د</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-3 flex space-x-1">
                            <Link
                                :href="route('menu-items.show', item.id)"
                                class="flex-1 rounded-lg bg-blue-500 px-2 py-1.5 text-center text-xs font-medium text-white transition-colors hover:bg-blue-600"
                            >
                                عرض
                            </Link>
                            <Link
                                :href="route('menu-items.edit', item.id)"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                تعديل
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredMenuItems.length === 0" class="flex flex-col items-center justify-center py-16">
                <div class="rounded-full bg-gradient-to-br from-blue-50 to-purple-50 p-6 dark:from-blue-900/20 dark:to-purple-900/20">
                    <Menu class="h-16 w-16 text-blue-500 dark:text-blue-400" />
                </div>
                <h3 class="mt-6 text-xl font-bold text-gray-900 dark:text-white">
                    {{ selectedRestaurantId ? 'لا توجد منتجات في هذا المطعم' : 'لا توجد منتجات في القائمة' }}
                </h3>
                <p class="mt-2 text-center text-gray-600 dark:text-gray-400">
                    {{ selectedRestaurantId ? 'جرب اختيار مطعم آخر أو أضف منتجات جديدة' : 'ابدأ بإضافة منتجات جديدة للمطاعم' }}
                </p>
                <Link
                    :href="route('menu-items.create')"
                    class="mt-6 group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-purple-700 hover:shadow-xl hover:scale-105"
                >
                    <Plus class="h-4 w-4 transition-transform group-hover:rotate-90" />
                    إضافة منتج جديد
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
