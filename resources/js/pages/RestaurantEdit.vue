<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, Store, MapPin, Phone, Mail, Clock, DollarSign, Trash2, Edit, Settings, Info } from 'lucide-vue-next';

interface Props {
    restaurant: {
        id: number;
        name: string;
        description: string;
        address: string;
        phone: string;
        email: string;
        logo: string;
        cover_image: string;
        is_active: boolean;
        opening_time: string;
        closing_time: string;
        delivery_fee: number;
        delivery_time: number;
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
        title: 'Edit Restaurant',
        href: `/restaurants/${props.restaurant.id}/edit`,
    },
];

const form = useForm({
    name: props.restaurant.name,
    description: props.restaurant.description,
    address: props.restaurant.address,
    phone: props.restaurant.phone,
    email: props.restaurant.email,
    logo: null as File | null,
    is_active: props.restaurant.is_active,
    opening_time: props.restaurant.opening_time,
    closing_time: props.restaurant.closing_time,
    delivery_fee: props.restaurant.delivery_fee,
    delivery_time: props.restaurant.delivery_time,
});

const submit = () => {
    form.put(route('restaurants.update', props.restaurant.id), {
        onSuccess: () => {
            // Don't reset the form to keep the updated values
        },
        onError: (errors) => {
            console.log('Form errors:', errors);
        },
    });
};

const deleteRestaurant = () => {
    if (confirm(`هل أنت متأكد من حذف المطعم "${props.restaurant.name}"؟ هذا الإجراء لا يمكن التراجع عنه.`)) {
        router.delete(route('restaurants.destroy', props.restaurant.id));
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};

const formatTime = (time: string) => {
    if (!time) return '';
    return new Date(`2000-01-01T${time}`).toLocaleTimeString('ar-SA', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};
</script>

<template>
    <Head title="Edit Restaurant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <!-- Enhanced Header Section -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 shadow-lg">
                <div class="absolute inset-0 bg-gradient-to-r from-green-600/10 via-blue-600/10 to-purple-600/10"></div>
                <div class="relative px-6 py-10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <Link
                                :href="route('restaurants.index')"
                                class="group inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white/90 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-white hover:shadow-lg dark:border-gray-600 dark:bg-gray-800/90 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                                العودة للقائمة
                            </Link>
                            <div class="flex items-center space-x-4">
                                <div class="rounded-2xl bg-gradient-to-r from-green-500 via-blue-500 to-purple-500 p-4 shadow-xl">
                                    <Edit class="h-8 w-8 text-white" />
                                </div>
                                <div>
                                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white">تعديل المطعم</h1>
                                    <p class="text-lg text-gray-600 dark:text-gray-400">تحديث معلومات المطعم وإعداداته</p>
                                </div>
                            </div>
                        </div>
                        <div class="hidden lg:block">
                            <div v-if="restaurant.logo && restaurant.logo !== ''" class="flex items-center justify-center">
                                <div class="h-24 w-24 overflow-hidden rounded-2xl shadow-xl">
                                    <img
                                        :src="`/storage/${restaurant.logo}`"
                                        :alt="`${restaurant.name} Logo`"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                            <div v-else class="flex items-center justify-center">
                                <div class="h-24 w-24 rounded-2xl bg-gray-100 flex items-center justify-center shadow-xl dark:bg-gray-700">
                                    <Store class="h-12 w-12 text-gray-400" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Form Section -->
            <div class="px-6 py-10">
                <div class="mx-auto max-w-7xl">
                    <div class="grid grid-cols-1 gap-10 xl:grid-cols-4">
                        <!-- Main Form -->
                        <div class="xl:col-span-3">
                            <div class="rounded-3xl bg-white p-10 shadow-2xl dark:bg-gray-800">
                                <div class="mb-10">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="rounded-xl bg-gradient-to-r from-green-500 to-blue-500 p-3">
                                            <Settings class="h-6 w-6 text-white" />
                                        </div>
                                        <div>
                                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">معلومات المطعم الأساسية</h2>
                                            <p class="text-gray-600 dark:text-gray-400">تحديث التفاصيل الأساسية للمطعم</p>
                                        </div>
                                    </div>
                                </div>

                                <form @submit.prevent="submit" class="space-y-8" enctype="multipart/form-data">
                                    <!-- Restaurant Name & Description -->
                                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                                        <div class="space-y-3">
                                            <Label for="name" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                اسم المطعم <span class="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                id="name"
                                                v-model="form.name"
                                                type="text"
                                                placeholder="مثال: مطعم بيتزا القصر"
                                                class="rounded-xl border-gray-200 px-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <InputError :message="form.errors.name" />
                                        </div>

                                        <div class="space-y-3">
                                            <Label for="description" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                وصف المطعم
                                            </Label>
                                            <textarea
                                                id="description"
                                                v-model="form.description"
                                                rows="3"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="وصف تفصيلي للمطعم..."
                                            ></textarea>
                                            <InputError :message="form.errors.description" />
                                        </div>
                                    </div>

                                    <!-- Address -->
                                    <div class="space-y-3">
                                        <Label for="address" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            العنوان <span class="text-red-500">*</span>
                                        </Label>
                                        <div class="relative">
                                            <Input
                                                id="address"
                                                v-model="form.address"
                                                type="text"
                                                placeholder="مثال: شارع الملك فهد، الرياض"
                                                class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                <MapPin class="h-5 w-5 text-gray-400" />
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.address" />
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                        <div class="space-y-3">
                                            <Label for="phone" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                رقم الهاتف <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="phone"
                                                    v-model="form.phone"
                                                    type="tel"
                                                    placeholder="+966-50-123-4567"
                                                    class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <Phone class="h-5 w-5 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.phone" />
                                        </div>

                                        <div class="space-y-3">
                                            <Label for="email" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                البريد الإلكتروني <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="email"
                                                    v-model="form.email"
                                                    type="email"
                                                    placeholder="info@restaurant.com"
                                                    class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <Mail class="h-5 w-5 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.email" />
                                        </div>
                                    </div>

                                    <!-- Restaurant Logo -->
                                    <div class="space-y-4">
                                        <Label for="logo" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            شعار المطعم
                                        </Label>
                                        <div class="space-y-6">
                                            <!-- Logo Preview -->
                                            <div v-if="form.logo || (restaurant.logo && restaurant.logo !== '')" class="relative">
                                                <img
                                                    :src="form.logo ? (window as any).URL.createObjectURL(form.logo) : `/storage/${restaurant.logo}`"
                                                    alt="Logo Preview"
                                                    class="h-48 w-full rounded-2xl object-cover border-2 border-gray-200 shadow-lg"
                                                />
                                                <button
                                                    @click="form.logo = null"
                                                    type="button"
                                                    class="absolute top-4 right-4 rounded-full bg-red-500 p-2 text-white hover:bg-red-600 shadow-lg transition-all"
                                                >
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Logo File Input -->
                                            <div v-if="!form.logo && (!restaurant.logo || restaurant.logo === '')" class="flex justify-center">
                                                <div class="w-full">
                                                    <label
                                                        for="logo-upload"
                                                        class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-8 transition-all hover:border-green-500 hover:bg-green-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                                                    >
                                                        <div class="flex flex-col items-center justify-center space-y-4">
                                                            <div class="rounded-full bg-green-100 p-4 dark:bg-green-900/20">
                                                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                                </svg>
                                                            </div>
                                                            <div class="text-center">
                                                                <div class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                                                    <span class="font-bold text-green-600 hover:text-green-500">إضافة شعار المطعم</span>
                                                                </div>
                                                                <p class="text-sm text-gray-500 mt-2">PNG, JPG حتى 2MB</p>
                                                            </div>
                                                        </div>
                                                    </label>
                                                    <input
                                                        id="logo-upload"
                                                        type="file"
                                                        accept="image/*"
                                                        class="hidden"
                                                        @change="(event) => {
                                                            const target = event.target as HTMLInputElement;
                                                            if (target && target.files && target.files[0]) {
                                                                form.logo = target.files[0];
                                                            }
                                                        }"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.logo" />
                                    </div>

                                    <!-- Operating Hours -->
                                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                        <div class="space-y-3">
                                            <Label for="opening_time" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                وقت الفتح <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="opening_time"
                                                    v-model="form.opening_time"
                                                    type="time"
                                                    class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <Clock class="h-5 w-5 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.opening_time" />
                                        </div>

                                        <div class="space-y-3">
                                            <Label for="closing_time" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                وقت الإغلاق <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="closing_time"
                                                    v-model="form.closing_time"
                                                    type="time"
                                                    class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <Clock class="h-5 w-5 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.closing_time" />
                                        </div>
                                    </div>

                                    <!-- Delivery Settings -->
                                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                        <div class="space-y-3">
                                            <Label for="delivery_fee" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                رسوم التوصيل <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">ر.س</span>
                                                <Input
                                                    id="delivery_fee"
                                                    v-model="form.delivery_fee"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                            </div>
                                            <InputError :message="form.errors.delivery_fee" />
                                        </div>

                                        <div class="space-y-3">
                                            <Label for="delivery_time" class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                وقت التوصيل (دقيقة) <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="delivery_time"
                                                    v-model="form.delivery_time"
                                                    type="number"
                                                    min="1"
                                                    placeholder="30"
                                                    class="rounded-xl border-gray-200 pl-12 pr-4 py-4 text-base transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                                    <Clock class="h-5 w-5 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.delivery_time" />
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="rounded-2xl border border-gray-200 bg-gradient-to-r from-gray-50 to-green-50 p-8 dark:border-gray-600 dark:from-gray-700 dark:to-green-900/20">
                                        <h3 class="mb-6 text-lg font-semibold text-gray-900 dark:text-white">حالة المطعم</h3>
                                        <div class="flex items-center space-x-4">
                                            <Checkbox id="is_active" v-model="form.is_active" class="h-5 w-5" />
                                            <Label for="is_active" class="text-base text-gray-700 dark:text-gray-300">المطعم نشط ومتاح للطلبات</Label>
                                        </div>
                                    </div>

                                    <!-- Professional Action Buttons -->
                                    <div class="border-t border-gray-200 pt-8 dark:border-gray-700">
                                        <div class="flex flex-col space-y-4 lg:flex-row lg:space-y-0 lg:space-x-6">
                                            <!-- Primary Save Button -->
                                            <Button
                                                type="submit"
                                                class="group relative flex-1 overflow-hidden rounded-2xl bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 px-8 py-4 text-base font-bold text-white shadow-lg transition-all duration-300 hover:from-green-600 hover:via-emerald-600 hover:to-teal-600 hover:shadow-2xl hover:scale-105 disabled:opacity-50 disabled:hover:scale-100"
                                                :disabled="form.processing"
                                            >
                                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                                                <div class="relative flex items-center justify-center">
                                                    <Save class="mr-3 h-5 w-5 transition-transform duration-300 group-hover:scale-110" />
                                                    <span class="font-bold">{{ form.processing ? 'جاري الحفظ...' : 'حفظ التغييرات' }}</span>
                                                </div>
                                            </Button>

                                            <!-- Secondary Cancel Button -->
                                            <Link
                                                :href="route('restaurants.index')"
                                                class="group flex-1 rounded-2xl border-2 border-gray-300 bg-white px-8 py-4 text-center text-base font-bold text-gray-700 transition-all duration-300 hover:border-gray-400 hover:bg-gray-50 hover:shadow-lg hover:scale-105 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700"
                                            >
                                                <div class="flex items-center justify-center">
                                                    <span class="font-bold">إلغاء</span>
                                                </div>
                                            </Link>

                                            <!-- Danger Delete Button -->
                                            <button
                                                @click="deleteRestaurant"
                                                type="button"
                                                class="group flex-1 rounded-2xl border-2 border-red-300 bg-white px-8 py-4 text-center text-base font-bold text-red-600 transition-all duration-300 hover:border-red-400 hover:bg-red-50 hover:shadow-lg hover:scale-105 dark:border-red-600 dark:bg-gray-800 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-900/20"
                                            >
                                                <div class="flex items-center justify-center">
                                                    <Trash2 class="mr-3 h-5 w-5 transition-transform duration-300 group-hover:scale-110" />
                                                    <span class="font-bold">حذف المطعم</span>
                                                </div>
                                            </button>
                                        </div>

                                        <!-- Button Descriptions -->
                                        <div class="mt-4 grid grid-cols-1 gap-4 text-center text-sm text-gray-500 lg:grid-cols-3">
                                            <div class="flex items-center justify-center space-x-2">
                                                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                                <span>حفظ جميع التغييرات</span>
                                            </div>
                                            <div class="flex items-center justify-center space-x-2">
                                                <div class="h-2 w-2 rounded-full bg-gray-400"></div>
                                                <span>العودة بدون حفظ</span>
                                            </div>
                                            <div class="flex items-center justify-center space-x-2">
                                                <div class="h-2 w-2 rounded-full bg-red-500"></div>
                                                <span>حذف المطعم نهائياً</span>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Enhanced Sidebar -->
                        <div class="space-y-8">
                            <!-- Restaurant Info -->
                            <div class="rounded-3xl bg-white p-8 shadow-2xl dark:bg-gray-800">
                                <div class="mb-6 flex items-center space-x-4">
                                    <div class="rounded-xl bg-gradient-to-r from-green-500 to-blue-500 p-3">
                                        <Store class="h-6 w-6 text-white" />
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">معلومات المطعم</h3>
                                </div>
                                <div class="space-y-4">
                                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700">
                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ restaurant.name }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ restaurant.address }}</div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">الحالة:</span>
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
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

                            <!-- Enhanced Quick Stats -->
                            <div class="rounded-3xl bg-white p-8 shadow-2xl dark:bg-gray-800">
                                <div class="mb-6 flex items-center space-x-4">
                                    <div class="rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 p-3">
                                        <Info class="h-6 w-6 text-white" />
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">إحصائيات سريعة</h3>
                                </div>
                                <div class="space-y-6">
                                    <div class="rounded-xl bg-gradient-to-r from-green-50 to-blue-50 p-4 dark:from-green-900/20 dark:to-blue-900/20">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">رسوم التوصيل الحالية</span>
                                            <span class="text-lg font-bold text-green-600">{{ formatCurrency(restaurant.delivery_fee) }}</span>
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-gradient-to-r from-orange-50 to-red-50 p-4 dark:from-orange-900/20 dark:to-red-900/20">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">وقت التوصيل الحالي</span>
                                            <span class="text-lg font-bold text-orange-600">{{ restaurant.delivery_time }} دقيقة</span>
                                        </div>
                                    </div>
                                    <div class="rounded-xl bg-gradient-to-r from-purple-50 to-pink-50 p-4 dark:from-purple-900/20 dark:to-pink-900/20">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">ساعات العمل</span>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-purple-600">{{ formatTime(restaurant.opening_time) }}</div>
                                                <div class="text-xs text-gray-500">إلى</div>
                                                <div class="text-sm font-bold text-purple-600">{{ formatTime(restaurant.closing_time) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Tips -->
                            <div class="rounded-3xl bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 p-8 dark:from-green-900/20 dark:via-blue-900/20 dark:to-purple-900/20">
                                <div class="mb-6 flex items-center space-x-4">
                                    <div class="rounded-xl bg-gradient-to-r from-green-500 to-blue-500 p-3">
                                        <Info class="h-6 w-6 text-white" />
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">نصائح مهمة</h3>
                                </div>
                                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-start space-x-3">
                                        <div class="mt-2 h-2 w-2 rounded-full bg-green-500"></div>
                                        <span class="font-medium">تأكد من صحة معلومات الاتصال</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="mt-2 h-2 w-2 rounded-full bg-blue-500"></div>
                                        <span class="font-medium">حدد أوقات العمل بدقة</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="mt-2 h-2 w-2 rounded-full bg-purple-500"></div>
                                        <span class="font-medium">اضبط رسوم التوصيل المناسبة</span>
                                    </div>
                                    <div class="flex items-start space-x-3">
                                        <div class="mt-2 h-2 w-2 rounded-full bg-orange-500"></div>
                                        <span class="font-medium">حدد وقت التوصيل المتوقع</span>
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
