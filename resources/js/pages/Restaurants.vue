<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Plus, Store, Phone, Mail, Clock, MapPin, Trash2 } from 'lucide-vue-next';

interface Props {
    restaurants: Array<{
        id: number;
        name: string;
        description: string;
        address: string;
        phone: string;
        email: string;
        logo: string;
        is_active: boolean;
        opening_time: string;
        closing_time: string;
        delivery_fee: number;
        delivery_time: number;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'لوحة التحكم',
        href: '/dashboard',
    },
    {
        title: 'المطاعم',
        href: '/restaurants',
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};

const formatTime = (time: string) => {
    return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const deleteRestaurant = (restaurantId: number, restaurantName: string) => {
    if (confirm(`هل أنت متأكد من حذف المطعم "${restaurantName}"؟ هذا الإجراء لا يمكن التراجع عنه.`)) {
        router.delete(route('restaurants.destroy', restaurantId));
    }
};
</script>

<template>
    <Head title="المطاعم" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">المطاعم</h1>
                    <p class="text-muted-foreground">إدارة جميع المطاعم وإعداداتها</p>
                </div>
                <Link
                    :href="route('restaurants.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إضافة مطعم
                </Link>
            </div>

            <!-- Restaurants Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="restaurant in restaurants" :key="restaurant.id" class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-start space-x-4">
                        <!-- Restaurant Image/Logo -->
                        <div class="flex-shrink-0">
                            <div v-if="restaurant.logo && restaurant.logo !== ''" class="h-16 w-16 overflow-hidden rounded-lg">
                                <img
                                    :src="`/storage/${restaurant.logo}`"
                                    :alt="`${restaurant.name} Logo`"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div v-else class="h-16 w-16 rounded-lg bg-green-100 p-3 dark:bg-green-900/20 flex items-center justify-center">
                                <Store class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-semibold">{{ restaurant.name }}</h3>
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                        restaurant.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                    ]"
                                >
                                    {{ restaurant.is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">{{ restaurant.description }}</p>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                    <MapPin class="h-4 w-4" />
                                    <span>{{ restaurant.address }}</span>
                                </div>
                                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                    <Phone class="h-4 w-4" />
                                    <span>{{ restaurant.phone }}</span>
                                </div>
                                <div v-if="restaurant.email" class="flex items-center space-x-2 text-sm text-muted-foreground">
                                    <Mail class="h-4 w-4" />
                                    <span>{{ restaurant.email }}</span>
                                </div>
                                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                    <Clock class="h-4 w-4" />
                                    <span>{{ formatTime(restaurant.opening_time) }} - {{ formatTime(restaurant.closing_time) }}</span>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">رسوم التوصيل: {{ formatCurrency(restaurant.delivery_fee) }}</span>
                                <span class="text-muted-foreground">{{ restaurant.delivery_time }} دقيقة</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <Link
                            :href="route('restaurants.edit', restaurant.id)"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            تعديل
                        </Link>
                        <Link
                            :href="route('restaurants.show', restaurant.id)"
                            class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-700"
                        >
                            عرض
                        </Link>
                        <button
                            @click="deleteRestaurant(restaurant.id, restaurant.name)"
                            class="rounded-lg border border-red-300 bg-white px-3 py-2 text-center text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-600 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20"
                            title="حذف المطعم"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="restaurants.length === 0" class="flex flex-col items-center justify-center py-12">
                <Store class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">لا توجد مطاعم</h3>
                <p class="mt-2 text-muted-foreground">ابدأ بإضافة أول مطعم.</p>
                <Link
                    :href="route('restaurants.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إضافة مطعم
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
