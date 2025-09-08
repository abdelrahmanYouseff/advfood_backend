<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, Menu, Store, Tag, Clock } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    restaurants: Array<{
        id: number;
        name: string;
        logo: string;
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
    {
        title: 'Add Menu Item',
        href: '/menu-items/create',
    },
];

const form = useForm({
    restaurant_id: '',
    name: '',
    description: '',
    price: '',
    image: null,
    is_available: true,
    is_featured: false,
    preparation_time: 15,
    ingredients: '',
    allergens: '',
});

const selectedRestaurant = computed(() => {
    return props.restaurants.find(r => r.id == form.restaurant_id);
});

const submit = () => {
    form.post(route('menu-items.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};

// Computed property for image preview URL
const imagePreviewUrl = computed(() => {
    if (form.image && typeof window !== 'undefined' && window.URL) {
        try {
            return window.URL.createObjectURL(form.image);
        } catch (error) {
            console.error('Error creating object URL:', error);
            return null;
        }
    }
    return null;
});
</script>

<template>
    <Head title="Add Menu Item" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <!-- Header Section -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-purple-600/10"></div>
                <div class="relative px-6 py-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <Link
                                :href="route('menu-items.index')"
                                class="group inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white/80 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-white hover:shadow-md dark:border-gray-600 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                                العودة للقائمة
                            </Link>
                            <div class="flex items-center space-x-3">
                                <div class="rounded-xl bg-gradient-to-r from-orange-500 to-red-500 p-3 shadow-lg">
                                    <Menu class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">إضافة منتج جديد</h1>
                                    <p class="text-gray-600 dark:text-gray-400">أضف منتج جديد إلى قائمة المطعم</p>
                                </div>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            <div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 p-4 text-white shadow-lg">
                                <div v-if="selectedRestaurant && selectedRestaurant.logo && selectedRestaurant.logo !== ''" class="flex items-center space-x-3">
                                    <div class="h-12 w-12 overflow-hidden rounded-lg border-2 border-white/20">
                                        <img
                                            :src="`/storage/${selectedRestaurant.logo}`"
                                            :alt="`${selectedRestaurant.name} Logo`"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium">المطعم</div>
                                        <div class="text-lg font-bold">{{ selectedRestaurant.name }}</div>
                                    </div>
                                </div>
                                <div v-else class="flex items-center space-x-3">
                                    <div class="h-12 w-12 rounded-lg bg-white/20 flex items-center justify-center">
                                        <Store class="h-6 w-6 text-white" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium">اختر المطعم</div>
                                        <div class="text-lg font-bold">لإضافة المنتج</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Section -->
            <div class="px-6 py-8">
                <div class="mx-auto max-w-4xl">
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <!-- Main Form -->
                        <div class="lg:col-span-2">
                            <div class="rounded-2xl bg-white p-8 shadow-xl dark:bg-gray-800">
                                <div class="mb-8">
                                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">معلومات المنتج الأساسية</h2>
                                    <p class="text-gray-600 dark:text-gray-400">أدخل التفاصيل الأساسية للمنتج</p>
                                </div>

                                <form @submit.prevent="submit" class="space-y-6">
                                    <!-- Restaurant & Category Selection -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="restaurant_id" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                المطعم <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <select
                                                    id="restaurant_id"
                                                    v-model="form.restaurant_id"
                                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    required
                                                >
                                                    <option value="">اختر المطعم</option>
                                                    <option v-for="restaurant in restaurants" :key="restaurant.id" :value="restaurant.id">
                                                        {{ restaurant.name }}
                                                    </option>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                    <Store class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.restaurant_id" />

                                            <!-- Restaurant Preview -->
                                            <div v-if="selectedRestaurant" class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700">
                                                <div class="flex items-center space-x-3">
                                                    <div v-if="selectedRestaurant.logo && selectedRestaurant.logo !== ''" class="h-10 w-10 overflow-hidden rounded-lg">
                                                        <img
                                                            :src="`/storage/${selectedRestaurant.logo}`"
                                                            :alt="`${selectedRestaurant.name} Logo`"
                                                            class="h-full w-full object-cover"
                                                        />
                                                    </div>
                                                    <div v-else class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center dark:bg-gray-600">
                                                        <Store class="h-5 w-5 text-gray-400" />
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedRestaurant.name }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Product Name -->
                                    <div class="space-y-2">
                                        <Label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            اسم المنتج <span class="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            placeholder="مثال: بيتزا مارجريتا"
                                            class="rounded-xl border-gray-200 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700"
                                            required
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <!-- Description -->
                                    <div class="space-y-2">
                                        <Label for="description" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            وصف المنتج
                                        </Label>
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="4"
                                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="وصف تفصيلي للمنتج..."
                                        ></textarea>
                                        <InputError :message="form.errors.description" />
                                    </div>

                                    <!-- Product Image -->
                                    <div class="space-y-2">
                                        <Label for="image" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            صورة المنتج
                                        </Label>
                                        <div class="space-y-4">
                                            <!-- Image Preview -->
                                            <div v-if="form.image && imagePreviewUrl" class="relative">
                                                <img
                                                    :src="imagePreviewUrl"
                                                    alt="Preview"
                                                    class="h-48 w-full rounded-xl object-cover border border-gray-200"
                                                />
                                                <button
                                                    @click="form.image = null"
                                                    type="button"
                                                    class="absolute top-2 right-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- File Input -->
                                            <div v-if="!form.image" class="flex justify-center">
                                                <div class="w-full">
                                                    <label
                                                        for="image-upload"
                                                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 transition-all hover:border-blue-500 hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-blue-500 dark:hover:bg-blue-900/20"
                                                    >
                                                        <div class="flex flex-col items-center justify-center space-y-2">
                                                            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                            </svg>
                                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                                <span class="font-medium text-blue-600 hover:text-blue-500">اضغط لاختيار صورة</span>
                                                                <span class="text-gray-500"> أو اسحب وأفلت</span>
                                                            </div>
                                                            <p class="text-xs text-gray-500">PNG, JPG, GIF حتى 2MB</p>
                                                        </div>
                                                    </label>
                                                    <input
                                                        id="image-upload"
                                                        type="file"
                                                        accept="image/*"
                                                        class="hidden"
                                                        @change="form.image = $event.target.files[0]"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.image" />
                                    </div>

                                    <!-- Price & Preparation Time -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="price" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                السعر <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">ر.س</span>
                                                <Input
                                                    id="price"
                                                    v-model="form.price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    class="rounded-xl border-gray-200 pl-8 pr-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                    required
                                                />
                                            </div>
                                            <InputError :message="form.errors.price" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="preparation_time" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                وقت التحضير (دقيقة)
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="preparation_time"
                                                    v-model="form.preparation_time"
                                                    type="number"
                                                    min="1"
                                                    class="rounded-xl border-gray-200 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                    <Clock class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.preparation_time" />
                                        </div>
                                    </div>

                                    <!-- Ingredients & Allergens -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="ingredients" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                المكونات
                                            </Label>
                                            <textarea
                                                id="ingredients"
                                                v-model="form.ingredients"
                                                rows="4"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="قائمة المكونات..."
                                            ></textarea>
                                            <InputError :message="form.errors.ingredients" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="allergens" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                مسببات الحساسية
                                            </Label>
                                            <textarea
                                                id="allergens"
                                                v-model="form.allergens"
                                                rows="4"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="مسببات الحساسية إن وجدت..."
                                            ></textarea>
                                            <InputError :message="form.errors.allergens" />
                                        </div>
                                    </div>

                                    <!-- Options -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700">
                                        <h3 class="mb-4 text-sm font-medium text-gray-900 dark:text-white">خيارات المنتج</h3>
                                        <div class="space-y-4">
                                            <div class="flex items-center space-x-3">
                                                <Checkbox id="is_available" v-model="form.is_available" />
                                                <Label for="is_available" class="text-sm text-gray-700 dark:text-gray-300">متاح للطلب</Label>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <Checkbox id="is_featured" v-model="form.is_featured" />
                                                <Label for="is_featured" class="text-sm text-gray-700 dark:text-gray-300">منتج مميز</Label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                                        <Button
                                            type="submit"
                                            class="flex-1 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3 text-sm font-medium text-white shadow-lg transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-xl disabled:opacity-50"
                                            :disabled="form.processing"
                                        >
                                            <Save class="mr-2 h-4 w-4" />
                                            {{ form.processing ? 'جاري الحفظ...' : 'حفظ المنتج' }}
                                        </Button>
                                        <Link
                                            :href="route('menu-items.index')"
                                            class="flex-1 rounded-xl border border-gray-200 bg-white px-6 py-3 text-center text-sm font-medium text-gray-700 transition-all hover:bg-gray-50 hover:shadow-md dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                        >
                                            إلغاء
                                        </Link>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">
                            <!-- Restaurant Info -->
                            <div v-if="selectedRestaurant" class="rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                <div class="mb-4 flex items-center space-x-3">
                                    <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/20">
                                        <Store class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">معلومات المطعم</h3>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedRestaurant.name }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tips -->
                            <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-purple-50 p-6 dark:from-blue-900/20 dark:to-purple-900/20">
                                <div class="mb-4 flex items-center space-x-3">
                                    <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/20">
                                        <Menu class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">نصائح</h3>
                                </div>
                                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>اكتب وصفاً واضحاً ومفصلاً للمنتج</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>أضف جميع المكونات المهمة</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>اذكر مسببات الحساسية إن وجدت</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>حدد وقت التحضير بدقة</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">إحصائيات سريعة</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">إجمالي المطاعم</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ restaurants.length }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">إجمالي المنتجات</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">0</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">المنتجات المميزة</span>
                                        <span class="text-sm font-medium text-green-600">12</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
