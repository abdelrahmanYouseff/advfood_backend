<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Store, MapPin, Phone, Mail, Clock, DollarSign, Edit, Menu, Tag, Users, ShoppingCart, Trash2 } from 'lucide-vue-next';

interface Props {
    restaurant: {
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
        menuItems: Array<{
            id: number;
            name: string;
            price: number;
            is_available: boolean;
            is_featured: boolean;
        }>;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Restaurants',
        href: '/restaurants',
    },
    {
        title: props.restaurant.name,
        href: `/restaurants/${props.restaurant.id}`,
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

const deleteRestaurant = () => {
    if (confirm(`هل أنت متأكد من حذف المطعم "${restaurant.name}"؟ هذا الإجراء لا يمكن التراجع عنه.`)) {
        router.delete(route('restaurants.destroy', restaurant.id));
    }
};
</script>

<template>
    <Head :title="`${restaurant.name} - Restaurant`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('restaurants.index')"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        العودة للقائمة
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">{{ restaurant.name }}</h1>
                        <p class="text-muted-foreground">تفاصيل المطعم</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <Link
                        :href="route('restaurants.edit', restaurant.id)"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        <Edit class="h-4 w-4" />
                        تعديل
                    </Link>
                    <button
                        @click="deleteRestaurant"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-600 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-900/20"
                    >
                        <Trash2 class="h-4 w-4" />
                        حذف
                    </button>
                </div>
            </div>

            <!-- Restaurant Details -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <!-- Restaurant Header -->
                        <div class="mb-6 flex items-center justify-between border-b pb-4">
                            <div class="flex items-center space-x-3">
                                <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/20">
                                    <Store class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold">{{ restaurant.name }}</h2>
                                    <div class="mt-1 flex space-x-2">
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
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <h3 class="mb-2 font-semibold">وصف المطعم</h3>
                            <p class="text-muted-foreground">{{ restaurant.description || 'لا يوجد وصف متاح' }}</p>
                        </div>

                        <!-- Restaurant Logo -->
                        <div v-if="restaurant.logo && restaurant.logo !== ''" class="mb-6">
                            <h3 class="mb-3 font-semibold">شعار المطعم</h3>
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <img
                                    :src="`/storage/${restaurant.logo}`"
                                    :alt="`${restaurant.name} Logo`"
                                    class="h-48 w-full object-cover"
                                />
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <h3 class="mb-3 font-semibold">معلومات الاتصال</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <MapPin class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ restaurant.address }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Phone class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ restaurant.phone }}</span>
                                    </div>
                                    <div v-if="restaurant.email" class="flex items-center space-x-2">
                                        <Mail class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ restaurant.email }}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="mb-3 font-semibold">ساعات العمل</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <Clock class="h-4 w-4 text-muted-foreground" />
                                        <span>{{ formatTime(restaurant.opening_time) }} - {{ formatTime(restaurant.closing_time) }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                                        <span>رسوم التوصيل: {{ formatCurrency(restaurant.delivery_fee) }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Clock class="h-4 w-4 text-muted-foreground" />
                                        <span>وقت التوصيل: {{ restaurant.delivery_time }} دقيقة</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div v-if="restaurant.categories.length > 0" class="mb-6">
                            <h3 class="mb-3 font-semibold">فئات الطعام</h3>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div
                                    v-for="category in restaurant.categories"
                                    :key="category.id"
                                    class="rounded-lg border bg-gray-50 p-4 dark:bg-gray-800"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <Tag class="h-4 w-4 text-blue-600" />
                                            <span class="font-medium">{{ category.name }}</span>
                                        </div>
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                                category.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                            ]"
                                        >
                                            {{ category.is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </div>
                                    <p v-if="category.description" class="mt-2 text-sm text-muted-foreground">
                                        {{ category.description }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Items -->
                        <div v-if="restaurant.menuItems.length > 0">
                            <h3 class="mb-3 font-semibold">منتجات القائمة</h3>
                            <div class="rounded-lg border">
                                <table class="w-full">
                                    <thead class="border-b bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-medium">المنتج</th>
                                            <th class="px-4 py-3 text-center text-sm font-medium">السعر</th>
                                            <th class="px-4 py-3 text-center text-sm font-medium">الحالة</th>
                                            <th class="px-4 py-3 text-center text-sm font-medium">التميز</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="item in restaurant.menuItems" :key="item.id">
                                            <td class="px-4 py-3 text-sm">{{ item.name }}</td>
                                            <td class="px-4 py-3 text-center text-sm font-medium">{{ formatCurrency(item.price) }}</td>
                                            <td class="px-4 py-3 text-center">
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
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    v-if="item.is_featured"
                                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400"
                                                >
                                                    مميز
                                                </span>
                                                <span v-else class="text-sm text-muted-foreground">-</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
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
                                :href="route('restaurants.edit', restaurant.id)"
                                class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                <Edit class="mr-2 h-4 w-4" />
                                تعديل المطعم
                            </Link>
                            <Link
                                :href="route('menu-items.create')"
                                class="flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Menu class="mr-2 h-4 w-4" />
                                إضافة منتج
                            </Link>
                        </div>
                    </div>

                    <!-- Restaurant Stats -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold">إحصائيات المطعم</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">الحالة:</span>
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
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">عدد الفئات:</span>
                                <span class="text-sm font-medium">{{ restaurant.categories.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">عدد المنتجات:</span>
                                <span class="text-sm font-medium">{{ restaurant.menuItems.length }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">رسوم التوصيل:</span>
                                <span class="text-sm font-medium">{{ formatCurrency(restaurant.delivery_fee) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">وقت التوصيل:</span>
                                <span class="text-sm">{{ restaurant.delivery_time }} دقيقة</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="rounded-xl border bg-card p-6 shadow-sm">
                        <h3 class="mb-4 font-semibold">روابط سريعة</h3>
                        <div class="space-y-3">
                            <Link
                                :href="route('menu-items.index')"
                                class="flex items-center space-x-2 rounded-lg p-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Menu class="h-4 w-4" />
                                <span>إدارة المنتجات</span>
                            </Link>
                            <Link
                                :href="route('categories.index')"
                                class="flex items-center space-x-2 rounded-lg p-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <Tag class="h-4 w-4" />
                                <span>إدارة الفئات</span>
                            </Link>
                            <Link
                                :href="route('orders.index')"
                                class="flex items-center space-x-2 rounded-lg p-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                <ShoppingCart class="h-4 w-4" />
                                <span>إدارة الطلبات</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
