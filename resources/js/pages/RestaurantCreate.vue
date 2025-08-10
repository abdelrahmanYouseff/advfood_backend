<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, Store, MapPin, Phone, Mail, Clock, DollarSign } from 'lucide-vue-next';
import { computed } from 'vue';

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
        title: 'Add Restaurant',
        href: '/restaurants/create',
    },
];

const form = useForm({
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
    logo: null,
    is_active: true,
    opening_time: '10:00:00',
    closing_time: '22:00:00',
    delivery_fee: 0,
    delivery_time: 30,
}, {
    forceFormData: true,
});

const submit = () => {
    form.post(route('restaurants.store'), {
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.log('Form errors:', errors);
        },
    });
};

// Computed property for logo preview URL
const logoPreviewUrl = computed(() => {
    if (form.logo && typeof window !== 'undefined' && window.URL) {
        try {
            return window.URL.createObjectURL(form.logo);
        } catch (error) {
            console.error('Error creating object URL:', error);
            return null;
        }
    }
    return null;
});
</script>

<template>
    <Head title="Add Restaurant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <!-- Header Section -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 shadow-sm">
                <div class="absolute inset-0 bg-gradient-to-r from-green-600/10 to-blue-600/10"></div>
                <div class="relative px-6 py-8">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <Link
                                :href="route('restaurants.index')"
                                class="group inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white/80 px-4 py-2 text-sm font-medium text-gray-700 transition-all hover:bg-white hover:shadow-md dark:border-gray-600 dark:bg-gray-800/80 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <ArrowLeft class="h-4 w-4 transition-transform group-hover:-translate-x-1" />
                                العودة للقائمة
                            </Link>
                            <div class="flex items-center space-x-3">
                                <div class="rounded-xl bg-gradient-to-r from-green-500 to-blue-500 p-3 shadow-lg">
                                    <Store class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">إضافة مطعم جديد</h1>
                                    <p class="text-gray-600 dark:text-gray-400">أضف مطعم جديد إلى النظام</p>
                                </div>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            <div class="rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 p-4 text-white shadow-lg">
                                <div class="text-sm font-medium">مطعم جديد</div>
                                <div class="text-2xl font-bold">+1</div>
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
                                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">معلومات المطعم الأساسية</h2>
                                    <p class="text-gray-600 dark:text-gray-400">أدخل التفاصيل الأساسية للمطعم الجديد</p>
                                </div>

                                <form @submit.prevent="submit" class="space-y-6" enctype="multipart/form-data">
                                    <!-- Restaurant Name -->
                                    <div class="space-y-2">
                                        <Label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            اسم المطعم <span class="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            placeholder="مثال: مطعم بيتزا القصر"
                                            class="rounded-xl border-gray-200 px-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                            required
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <!-- Description -->
                                    <div class="space-y-2">
                                        <Label for="description" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            وصف المطعم
                                        </Label>
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="4"
                                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="وصف تفصيلي للمطعم..."
                                        ></textarea>
                                        <InputError :message="form.errors.description" />
                                    </div>

                                    <!-- Address -->
                                    <div class="space-y-2">
                                        <Label for="address" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            العنوان <span class="text-red-500">*</span>
                                        </Label>
                                        <div class="relative">
                                            <Input
                                                id="address"
                                                v-model="form.address"
                                                type="text"
                                                placeholder="مثال: شارع الملك فهد، الرياض"
                                                class="rounded-xl border-gray-200 pl-10 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                required
                                            />
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <MapPin class="h-4 w-4 text-gray-400" />
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.address" />
                                    </div>

                                    <!-- Contact Information -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="phone" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                رقم الهاتف <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="phone"
                                                    v-model="form.phone"
                                                    type="tel"
                                                    placeholder="+966-50-123-4567"
                                                    class="rounded-xl border-gray-200 pl-10 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                    required
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <Phone class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.phone" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                البريد الإلكتروني
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="email"
                                                    v-model="form.email"
                                                    type="email"
                                                    placeholder="info@restaurant.com"
                                                    class="rounded-xl border-gray-200 pl-10 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <Mail class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.email" />
                                        </div>
                                    </div>

                                    <!-- Restaurant Logo -->
                                    <div class="space-y-2">
                                        <Label for="logo" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            شعار المطعم
                                        </Label>
                                        <div class="space-y-4">
                                            <!-- Logo Preview -->
                                            <div v-if="form.logo && form.logo !== null && logoPreviewUrl" class="relative">
                                                <img
                                                    :src="logoPreviewUrl"
                                                    alt="Logo Preview"
                                                    class="h-32 w-full rounded-xl object-cover border border-gray-200"
                                                />
                                                <button
                                                    @click="form.logo = null"
                                                    type="button"
                                                    class="absolute top-2 right-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Logo File Input -->
                                            <div v-if="!form.logo || form.logo === null" class="flex justify-center">
                                                <div class="w-full">
                                                    <label
                                                        for="logo-upload"
                                                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4 transition-all hover:border-green-500 hover:bg-green-50 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-green-500 dark:hover:bg-green-900/20"
                                                    >
                                                        <div class="flex flex-col items-center justify-center space-y-2">
                                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                            </svg>
                                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                                <span class="font-medium text-green-600 hover:text-green-500">إضافة شعار</span>
                                                            </div>
                                                            <p class="text-xs text-gray-500">PNG, JPG حتى 2MB</p>
                                                        </div>
                                                    </label>
                                                    <input
                                                        id="logo-upload"
                                                        type="file"
                                                        accept="image/*"
                                                        class="hidden"
                                                        @change="form.logo = $event.target.files[0]"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.logo" />
                                    </div>

                                    <!-- Operating Hours -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="opening_time" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                وقت الفتح <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="opening_time"
                                                    v-model="form.opening_time"
                                                    type="time"
                                                    class="rounded-xl border-gray-200 pl-10 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                    required
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <Clock class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.opening_time" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="closing_time" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                وقت الإغلاق <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="closing_time"
                                                    v-model="form.closing_time"
                                                    type="time"
                                                    class="rounded-xl border-gray-200 pl-10 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                    required
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <Clock class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.closing_time" />
                                        </div>
                                    </div>

                                    <!-- Delivery Settings -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label for="delivery_fee" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                رسوم التوصيل <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">ر.س</span>
                                                <Input
                                                    id="delivery_fee"
                                                    v-model="form.delivery_fee"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    class="rounded-xl border-gray-200 pl-8 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                    required
                                                />
                                            </div>
                                            <InputError :message="form.errors.delivery_fee" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="delivery_time" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                وقت التوصيل (دقيقة) <span class="text-red-500">*</span>
                                            </Label>
                                            <div class="relative">
                                                <Input
                                                    id="delivery_time"
                                                    v-model="form.delivery_time"
                                                    type="number"
                                                    min="1"
                                                    placeholder="30"
                                                    class="rounded-xl border-gray-200 pl-10 pr-4 py-3 text-sm transition-all focus:border-green-500 focus:ring-2 focus:ring-green-500/20 dark:border-gray-600 dark:bg-gray-700"
                                                    required
                                                />
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <Clock class="h-4 w-4 text-gray-400" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.delivery_time" />
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700">
                                        <h3 class="mb-4 text-sm font-medium text-gray-900 dark:text-white">حالة المطعم</h3>
                                        <div class="flex items-center space-x-3">
                                            <Checkbox id="is_active" v-model="form.is_active" />
                                            <Label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">المطعم نشط ومتاح للطلبات</Label>
                                        </div>
                                    </div>

                                    <!-- Submit Buttons -->
                                    <div class="flex flex-col space-y-4 sm:flex-row sm:space-y-0 sm:space-x-4">
                                        <Button
                                            type="submit"
                                            class="flex-1 rounded-xl bg-gradient-to-r from-green-600 to-blue-600 px-6 py-3 text-sm font-medium text-white shadow-lg transition-all hover:from-green-700 hover:to-blue-700 hover:shadow-xl disabled:opacity-50"
                                            :disabled="form.processing"
                                        >
                                            <Save class="mr-2 h-4 w-4" />
                                            {{ form.processing ? 'جاري الحفظ...' : 'إضافة المطعم' }}
                                        </Button>
                                        <Link
                                            :href="route('restaurants.index')"
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
                            <!-- Tips -->
                            <div class="rounded-2xl bg-gradient-to-br from-green-50 to-blue-50 p-6 dark:from-green-900/20 dark:to-blue-900/20">
                                <div class="mb-4 flex items-center space-x-3">
                                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/20">
                                        <Store class="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">نصائح</h3>
                                </div>
                                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-green-500"></div>
                                        <span>أدخل اسم المطعم بوضوح</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-green-500"></div>
                                        <span>تأكد من صحة معلومات الاتصال</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-green-500"></div>
                                        <span>حدد أوقات العمل بدقة</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-green-500"></div>
                                        <span>اضبط رسوم التوصيل المناسبة</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">إحصائيات سريعة</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">إجمالي المطاعم</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">0</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">المطاعم النشطة</span>
                                        <span class="text-sm font-medium text-green-600">0</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">متوسط رسوم التوصيل</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">0 ر.س</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Steps -->
                            <div class="rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                                <h3 class="mb-4 font-semibold text-gray-900 dark:text-white">الخطوات التالية</h3>
                                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>إضافة فئات الطعام</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>إضافة منتجات القائمة</span>
                                    </div>
                                    <div class="flex items-start space-x-2">
                                        <div class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                                        <span>إعداد أسعار التوصيل</span>
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
