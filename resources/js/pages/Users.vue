<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Users, Mail, Calendar, Trash2, Edit, Eye, Phone, MapPin, Globe, Star, Award, RefreshCw } from 'lucide-vue-next';

interface Props {
    users: Array<{
        id: number;
        name: string;
        email: string;
        phone_number?: string;
        address?: string;
        country?: string;
        role: string;
        point_customer_id?: string;
        points?: number;
        points_tier?: string;
        created_at: string;
    }>;
}

const props = defineProps<Props>();

const refreshPoints = () => {
    router.reload({ only: ['users'] });
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'لوحة التحكم',
        href: '/dashboard',
    },
    {
        title: 'المستخدمين',
        href: '/users',
    },
];

const deleteUser = (userId: number, userName: string) => {
    if (confirm(`هل أنت متأكد من حذف ${userName}؟ لا يمكن التراجع عن هذا الإجراء.`)) {
        router.delete(route('users.destroy', userId), {
            onSuccess: () => {
                // Success message will be handled by the backend
            },
            onError: () => {
                alert('فشل في حذف المستخدم. يرجى المحاولة مرة أخرى.');
            }
        });
    }
};
</script>

<template>
    <Head title="المستخدمين" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">المستخدمين</h1>
                    <p class="text-muted-foreground">إدارة جميع المستخدمين المسجلين</p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="refreshPoints"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                    >
                        <RefreshCw class="h-4 w-4" />
                        تحديث النقاط
                    </button>
                    <Link
                        :href="route('users.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        <Plus class="h-4 w-4" />
                        إضافة مستخدم
                    </Link>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4" />
                                        المستخدم
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Mail class="h-4 w-4" />
                                        البريد الإلكتروني
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">الدور</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Phone class="h-4 w-4" />
                                        الهاتف
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Globe class="h-4 w-4" />
                                        البلد
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Star class="h-4 w-4" />
                                        النقاط
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Award class="h-4 w-4" />
                                        المستوى
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        تاريخ الانضمام
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <!-- User Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20">
                                            <Users class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ user.name }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ user.email }}
                                </td>

                                <!-- Role -->
                                <td class="px-6 py-4">
                                    <span v-if="user.role === 'admin'" class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                        مدير
                                    </span>
                                    <span v-else class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                        مستخدم
                                    </span>
                                </td>

                                <!-- Phone -->
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span v-if="user.phone_number">{{ user.phone_number }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                                </td>

                                <!-- Country -->
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span v-if="user.country">{{ user.country }}</span>
                                    <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                                </td>

                                <!-- Points -->
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-1">
                                        <Star class="h-4 w-4 text-yellow-500" />
                                        <span class="font-semibold">{{ user.points || 0 }}</span>
                                    </div>
                                </td>

                                <!-- Tier -->
                                <td class="px-6 py-4">
                                    <span v-if="user.points_tier === 'gold'" class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                        <Award class="mr-1 h-3 w-3" />
                                        ذهبي
                                    </span>
                                    <span v-else-if="user.points_tier === 'silver'" class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                                        <Award class="mr-1 h-3 w-3" />
                                        فضي
                                    </span>
                                    <span v-else class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                                        <Award class="mr-1 h-3 w-3" />
                                        برونزي
                                    </span>
                                </td>

                                <!-- Join Date -->
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ new Date(user.created_at).toLocaleDateString() }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link
                                            :href="route('users.show', user.id)"
                                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 p-2 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                            title="عرض"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                        <Link
                                            :href="route('users.edit', user.id)"
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                            title="تعديل"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                        <button
                                            @click="deleteUser(user.id, user.name)"
                                            class="inline-flex items-center justify-center rounded-lg bg-red-600 p-2 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                            title="حذف"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="users.length === 0" class="flex flex-col items-center justify-center py-12">
                <Users class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">لا توجد مستخدمين</h3>
                <p class="mt-2 text-muted-foreground">ابدأ بإنشاء أول مستخدم.</p>
                <Link
                    :href="route('users.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    إضافة مستخدم
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
