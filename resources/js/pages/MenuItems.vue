<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Menu, Store, DollarSign, Clock, Star, Filter, Tag, Trash2 } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

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
const page = usePage() as any;

// Menu items language (synced with sidebarLang)
const menuLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        menuLang.value = storedLang;
    }
}

onMounted(() => {
    if (typeof window === 'undefined') return;

    const handler = (event: Event) => {
        const lang = (event as CustomEvent).detail;
        if (lang === 'en' || lang === 'ar') {
            menuLang.value = lang;
        }
    };

    window.addEventListener('sidebar-lang-changed', handler as EventListener);

    onUnmounted(() => {
        window.removeEventListener('sidebar-lang-changed', handler as EventListener);
    });
});

const t = (ar: string, en: string) => (menuLang.value === 'ar' ? ar : en);

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
        title: t('لوحة التحكم', 'Dashboard'),
        href: '/dashboard',
    },
    {
        title: t('عناصر القائمة', 'Menu Items'),
        href: '/menu-items',
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};

// Delete function with confirmation
const deleteMenuItem = (item: any) => {
    if (confirm(t(
        `هل أنت متأكد من حذف "${item.name}"؟\n\nهذا الإجراء لا يمكن التراجع عنه.`,
        `Are you sure you want to delete "${item.name}"?\n\nThis action cannot be undone.`
    ))) {
        router.delete(route('menu-items.destroy', item.id), {
            onSuccess: () => {
                // Success message will be handled by the controller
            },
            onError: (errors) => {
                console.error('Error deleting menu item:', errors);
                alert(t(
                    'حدث خطأ أثناء حذف المنتج. يرجى المحاولة مرة أخرى.',
                    'An error occurred while deleting the item. Please try again.'
                ));
            }
        });
    }
};
</script>

<template>
    <Head :title="t('عناصر القائمة', 'Menu Items')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Success/Error Messages -->
            <div v-if="page.props.flash?.success" class="rounded-lg bg-green-50 border border-green-200 p-4 dark:bg-green-900/20 dark:border-green-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ page.props.flash.success }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="page.props.flash?.error" class="rounded-lg bg-red-50 border border-red-200 p-4 dark:bg-red-900/20 dark:border-red-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            {{ page.props.flash.error }}
                        </p>
                    </div>
                </div>
            </div>
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ t('عناصر القائمة', 'Menu Items') }}
                    </h1>
                    <p class="text-muted-foreground">
                        {{ t('إدارة قوائم الطعام للمطاعم', 'Manage restaurant food menus') }}
                    </p>
                </div>
                <Link
                    :href="route('menu-items.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-purple-700 hover:shadow-xl hover:scale-105"
                >
                    <Plus class="h-4 w-4 transition-transform group-hover:rotate-90" />
                    {{ t('إضافة منتج جديد', 'Add new item') }}
                </Link>
            </div>

            <!-- Filter Section -->
            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-blue-50 to-purple-50 p-4 dark:from-blue-900/20 dark:to-purple-900/20">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <Filter class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ t('فلتر المنتجات', 'Filter items') }}
                        </span>
                    </div>
                    <div class="relative">
                        <select
                            v-model="selectedRestaurantId"
                            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">{{ t('جميع المطاعم', 'All restaurants') }}</option>
                            <option v-for="restaurant in restaurants" :key="restaurant.id" :value="restaurant.id">
                                {{ restaurant.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span v-if="selectedRestaurantId">
                        <template v-if="menuLang === 'ar'">
                            {{ filteredMenuItems.length }} منتج في {{ getRestaurantName(parseInt(selectedRestaurantId)) }}
                        </template>
                        <template v-else>
                            {{ filteredMenuItems.length }} items in {{ getRestaurantName(parseInt(selectedRestaurantId)) }}
                        </template>
                    </span>
                    <span v-else>
                        <template v-if="menuLang === 'ar'">
                            {{ filteredMenuItems.length }} منتج إجمالي
                        </template>
                        <template v-else>
                            {{ filteredMenuItems.length }} total items
                        </template>
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
                                {{ item.is_available ? t('متاح', 'Available') : t('غير متاح', 'Unavailable') }}
                            </span>
                            <span
                                v-if="item.is_featured"
                                class="inline-flex items-center rounded-full bg-yellow-500 px-2 py-1 text-xs font-medium text-white shadow-sm"
                            >
                                {{ t('مميز', 'Featured') }}
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
                                <span class="line-clamp-1">
                                    {{ item.restaurant?.name || t('غير محدد', 'Not set') }}
                                </span>
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
                                {{ t('عرض', 'View') }}
                            </Link>
                            <Link
                                :href="route('menu-items.edit', item.id)"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-center text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            >
                                {{ t('تعديل', 'Edit') }}
                            </Link>
                            <button
                                @click="deleteMenuItem(item)"
                                class="rounded-lg bg-red-500 px-2 py-1.5 text-center text-xs font-medium text-white transition-colors hover:bg-red-600"
                                :title="t('حذف المنتج', 'Delete item')"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
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
                    <template v-if="selectedRestaurantId">
                        {{ t('لا توجد منتجات في هذا المطعم', 'No items in this restaurant') }}
                    </template>
                    <template v-else>
                        {{ t('لا توجد منتجات في القائمة', 'No items in the menu') }}
                    </template>
                </h3>
                <p class="mt-2 text-center text-gray-600 dark:text-gray-400">
                    <template v-if="selectedRestaurantId">
                        {{ t('جرب اختيار مطعم آخر أو أضف منتجات جديدة', 'Try selecting another restaurant or add new items') }}
                    </template>
                    <template v-else>
                        {{ t('ابدأ بإضافة منتجات جديدة للمطاعم', 'Start by adding new items to restaurants') }}
                    </template>
                </p>
                <Link
                    :href="route('menu-items.create')"
                    class="mt-6 group inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-purple-700 hover:shadow-xl hover:scale-105"
                >
                    <Plus class="h-4 w-4 transition-transform group-hover:rotate-90" />
                    {{ t('إضافة منتج جديد', 'Add new item') }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
