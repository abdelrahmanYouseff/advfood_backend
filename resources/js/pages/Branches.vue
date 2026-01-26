<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Plus, MapPin, Mail, Lock, Power, PowerOff } from 'lucide-vue-next';
import { ref } from 'vue';

interface Branch {
    id: number;
    name: string;
    email: string;
    latitude: number | null;
    longitude: number | null;
    status: string;
    created_at: string;
}

interface Props {
    branches: Branch[];
}

const { branches } = defineProps<Props>();

// Track which branches are being toggled
const togglingBranches = ref<Set<number>>(new Set());

// Format status for display
const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { label: string; class: string }> = {
        active: { label: 'نشط', class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
        inactive: { label: 'غير نشط', class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    };
    return statusMap[status] || { label: status, class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' };
};

// Toggle branch status
const toggleStatus = (branchId: number) => {
    if (togglingBranches.value.has(branchId)) return;
    
    togglingBranches.value.add(branchId);
    
    router.post(
        `/branches/${branchId}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingBranches.value.delete(branchId);
            },
        }
    );
};
</script>

<template>
    <Head title="الفروع" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 p-2">
                            <Building2 class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                الفروع
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                إدارة فروع النظام
                            </p>
                        </div>
                    </div>

                    <!-- Add Branch Button (Future) -->
                    <!-- <Link
                        href="/branches/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600"
                    >
                        <Plus class="h-4 w-4" />
                        <span>إضافة فرع</span>
                    </Link> -->
                </div>

                <!-- Branches Grid -->
                <div v-if="branches && branches.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="branch in branches"
                        :key="branch.id"
                        class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                    >
                        <!-- Status Badge -->
                        <div class="absolute right-4 top-4">
                            <span
                                :class="getStatusBadge(branch.status).class"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ getStatusBadge(branch.status).label }}
                            </span>
                        </div>

                        <!-- Branch Icon -->
                        <div class="mb-4 inline-flex rounded-lg bg-gradient-to-br from-purple-100 to-purple-200 p-3 dark:from-purple-900 dark:to-purple-800">
                            <Building2 class="h-6 w-6 text-purple-600 dark:text-purple-300" />
                        </div>

                        <!-- Branch Name -->
                        <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ branch.name }}
                        </h3>

                        <!-- Branch Details -->
                        <div class="space-y-3">
                            <!-- Email -->
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <Mail class="h-4 w-4 flex-shrink-0" />
                                <span class="truncate">{{ branch.email }}</span>
                            </div>

                            <!-- Location -->
                            <div v-if="branch.latitude && branch.longitude" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <MapPin class="h-4 w-4 flex-shrink-0" />
                                <a
                                    :href="`https://www.google.com/maps?q=${branch.latitude},${branch.longitude}`"
                                    target="_blank"
                                    class="truncate hover:text-purple-600 hover:underline dark:hover:text-purple-400"
                                >
                                    {{ branch.latitude.toFixed(4) }}, {{ branch.longitude.toFixed(4) }}
                                </a>
                            </div>
                            <div v-else class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
                                <MapPin class="h-4 w-4 flex-shrink-0" />
                                <span>لا يوجد موقع</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex gap-2">
                            <button
                                @click="toggleStatus(branch.id)"
                                :disabled="togglingBranches.has(branch.id)"
                                :class="[
                                    'flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                                    branch.status === 'active'
                                        ? 'border border-red-300 bg-white text-red-700 hover:bg-red-50 dark:border-red-600 dark:bg-gray-700 dark:text-red-400 dark:hover:bg-red-900/20'
                                        : 'border border-green-300 bg-white text-green-700 hover:bg-green-50 dark:border-green-600 dark:bg-gray-700 dark:text-green-400 dark:hover:bg-green-900/20',
                                    togglingBranches.has(branch.id) && 'opacity-50 cursor-not-allowed'
                                ]"
                            >
                                <span v-if="togglingBranches.has(branch.id)" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>جاري التحديث...</span>
                                </span>
                                <span v-else class="inline-flex items-center gap-2">
                                    <PowerOff v-if="branch.status === 'active'" class="h-4 w-4" />
                                    <Power v-else class="h-4 w-4" />
                                    <span>{{ branch.status === 'active' ? 'تعطيل الفرع' : 'تفعيل الفرع' }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-12 text-center dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="mb-4 rounded-full bg-gray-200 p-6 dark:bg-gray-700">
                        <Building2 class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        لا توجد فروع
                    </h3>
                    <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                        لم يتم إضافة أي فروع بعد
                    </p>
                    <!-- <Link
                        href="/branches/create"
                        class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600"
                    >
                        <Plus class="h-4 w-4" />
                        <span>إضافة أول فرع</span>
                    </Link> -->
                </div>
            </div>
        </div>
    </AppLayout>
</template>
