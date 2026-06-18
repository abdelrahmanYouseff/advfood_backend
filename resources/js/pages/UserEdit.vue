<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, Mail, Lock, Phone, MapPin, Globe } from 'lucide-vue-next';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        phone_number?: string;
        address?: string;
        country?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard' },
    { title: 'المستخدمين', href: '/users' },
    { title: 'تعديل المستخدم', href: '#' },
];

const form = useForm({
    name:                  props.user.name,
    email:                 props.user.email,
    role:                  props.user.role,
    phone_number:          props.user.phone_number ?? '',
    address:               props.user.address ?? '',
    country:               props.user.country ?? '',
    password:              '',
    password_confirmation: '',
    _method:               'PUT',
});

const submit = () => {
    form.post(route('users.update', props.user.id));
};
</script>

<template>
    <Head title="تعديل المستخدم" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col">

            <!-- Header -->
            <div class="border-b bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('users.index')"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            رجوع للمستخدمين
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">تعديل المستخدم</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ props.user.email }}</p>
                        </div>
                    </div>
                    <Button type="submit" form="edit-form" :disabled="form.processing" class="inline-flex items-center gap-2">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'جاري الحفظ...' : 'حفظ التعديلات' }}
                    </Button>
                </div>
            </div>

            <!-- Form -->
            <div class="px-6 py-8">
                <div class="mx-auto max-w-4xl">
                    <div class="rounded-2xl bg-white p-8 shadow-xl dark:bg-gray-800">
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">بيانات المستخدم</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">تعديل معلومات الحساب — كلمة المرور اختيارية (اتركها فارغة إذا لم ترد تغييرها)</p>
                        </div>

                        <form @submit.prevent="submit" id="edit-form" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                                <!-- الاسم -->
                                <div class="space-y-2">
                                    <Label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <User class="mr-1 inline h-4 w-4" />
                                        الاسم الكامل <span class="text-red-500">*</span>
                                    </Label>
                                    <Input id="name" v-model="form.name" type="text" placeholder="أدخل الاسم الكامل"
                                        :class="{ 'border-red-500': form.errors.name }" required />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- البريد الإلكتروني -->
                                <div class="space-y-2">
                                    <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Mail class="mr-1 inline h-4 w-4" />
                                        البريد الإلكتروني <span class="text-red-500">*</span>
                                    </Label>
                                    <Input id="email" v-model="form.email" type="email" placeholder="أدخل البريد الإلكتروني"
                                        :class="{ 'border-red-500': form.errors.email }" required />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <!-- كلمة المرور الجديدة -->
                                <div class="space-y-2">
                                    <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Lock class="mr-1 inline h-4 w-4" />
                                        كلمة مرور جديدة
                                        <span class="text-gray-400 text-xs font-normal">(اختياري)</span>
                                    </Label>
                                    <Input id="password" v-model="form.password" type="password" placeholder="اتركها فارغة إذا لم ترد التغيير"
                                        :class="{ 'border-red-500': form.errors.password }" />
                                    <InputError :message="form.errors.password" />
                                </div>

                                <!-- تأكيد كلمة المرور -->
                                <div class="space-y-2">
                                    <Label for="password_confirmation" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Lock class="mr-1 inline h-4 w-4" />
                                        تأكيد كلمة المرور
                                    </Label>
                                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="أعد إدخال كلمة المرور"
                                        :class="{ 'border-red-500': form.errors.password_confirmation }" />
                                    <InputError :message="form.errors.password_confirmation" />
                                </div>

                                <!-- الرول -->
                                <div class="space-y-2">
                                    <Label for="role" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        الصلاحية <span class="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="role"
                                        v-model="form.role"
                                        :class="[
                                            'w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                            { 'border-red-500': form.errors.role }
                                        ]"
                                        required
                                    >
                                        <option value="user">User — مستخدم</option>
                                        <option value="admin">Admin — مدير</option>
                                        <option value="accountant">Accountant — محاسب</option>
                                    </select>
                                    <InputError :message="form.errors.role" />
                                </div>

                                <!-- رقم الهاتف -->
                                <div class="space-y-2">
                                    <Label for="phone_number" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Phone class="mr-1 inline h-4 w-4" />
                                        رقم الهاتف
                                    </Label>
                                    <Input id="phone_number" v-model="form.phone_number" type="tel" placeholder="أدخل رقم الهاتف"
                                        :class="{ 'border-red-500': form.errors.phone_number }" />
                                    <InputError :message="form.errors.phone_number" />
                                </div>

                                <!-- الدولة -->
                                <div class="space-y-2">
                                    <Label for="country" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Globe class="mr-1 inline h-4 w-4" />
                                        الدولة
                                    </Label>
                                    <Input id="country" v-model="form.country" type="text" placeholder="أدخل الدولة"
                                        :class="{ 'border-red-500': form.errors.country }" />
                                    <InputError :message="form.errors.country" />
                                </div>
                            </div>

                            <!-- العنوان -->
                            <div class="space-y-2">
                                <Label for="address" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <MapPin class="mr-1 inline h-4 w-4" />
                                    العنوان
                                </Label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    placeholder="أدخل العنوان الكامل"
                                    :class="[
                                        'w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                        { 'border-red-500': form.errors.address }
                                    ]"
                                    rows="3"
                                ></textarea>
                                <InputError :message="form.errors.address" />
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="flex items-center justify-end gap-4 border-t pt-6">
                                <Link
                                    :href="route('users.index')"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    إلغاء
                                </Link>
                                <Button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2">
                                    <Save class="h-4 w-4" />
                                    {{ form.processing ? 'جاري الحفظ...' : 'حفظ التعديلات' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
