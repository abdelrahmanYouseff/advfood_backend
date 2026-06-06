<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Building2, MapPin, Mail, Power, PowerOff, MessageCircle, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Branch {
    id: number;
    name: string;
    email: string;
    latitude: number | null;
    longitude: number | null;
    status: string;
    whatsapp_alert_phones: string[] | null;
    created_at: string;
}

interface Props {
    branches: Branch[];
}

const { branches } = defineProps<Props>();
const page = usePage();

const togglingBranches = ref<Set<number>>(new Set());
const savingBranchId = ref<number | null>(null);
const testingBranchId = ref<number | null>(null);

const branchForms = ref<Record<number, { phones: string[]; testPhone: string }>>({});

const initBranchForm = (branch: Branch) => {
    if (!branchForms.value[branch.id]) {
        const phones = branch.whatsapp_alert_phones?.length
            ? [...branch.whatsapp_alert_phones]
            : [''];

        branchForms.value[branch.id] = {
            phones,
            testPhone: phones[0] ?? '',
        };
    }
};

branches.forEach(initBranchForm);

const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { label: string; class: string }> = {
        active: { label: 'نشط', class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
        inactive: { label: 'غير نشط', class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    };
    return statusMap[status] || { label: status, class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' };
};

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

const addPhoneField = (branchId: number) => {
    branchForms.value[branchId].phones.push('');
};

const removePhoneField = (branchId: number, index: number) => {
    const phones = branchForms.value[branchId].phones;
    if (phones.length === 1) {
        phones[0] = '';
        return;
    }
    phones.splice(index, 1);
};

const saveWhatsappPhones = (branchId: number) => {
    savingBranchId.value = branchId;

    const phones = branchForms.value[branchId].phones
        .map((phone) => phone.trim())
        .filter(Boolean);

    router.patch(
        route('branches.whatsapp-alert-phones.update', branchId),
        { whatsapp_alert_phones: phones },
        {
            preserveScroll: true,
            onSuccess: () => {
                branchForms.value[branchId].phones = phones.length ? phones : [''];
                branchForms.value[branchId].testPhone = phones[0] ?? '';
            },
            onFinish: () => {
                savingBranchId.value = null;
            },
        }
    );
};

const sendTestMessage = (branchId: number) => {
    testingBranchId.value = branchId;

    const testPhone = branchForms.value[branchId].testPhone.trim();

    router.post(
        route('branches.whatsapp-test-message', branchId),
        { phone: testPhone || undefined },
        {
            preserveScroll: true,
            onFinish: () => {
                testingBranchId.value = null;
            },
        }
    );
};

const whatsappTestError = computed(() => (page.props.errors as Record<string, string>)?.whatsapp_test);
</script>

<template>
    <Head title="الفروع" />

    <AppLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
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
                                إدارة فروع النظام وتنبيهات الواتساب
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="branches && branches.length > 0" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="branch in branches"
                        :key="branch.id"
                        class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div class="absolute right-4 top-4">
                            <span
                                :class="getStatusBadge(branch.status).class"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ getStatusBadge(branch.status).label }}
                            </span>
                        </div>

                        <div class="mb-4 inline-flex rounded-lg bg-gradient-to-br from-purple-100 to-purple-200 p-3 dark:from-purple-900 dark:to-purple-800">
                            <Building2 class="h-6 w-6 text-purple-600 dark:text-purple-300" />
                        </div>

                        <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-gray-100">
                            {{ branch.name }}
                        </h3>

                        <div class="space-y-3">
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <Mail class="h-4 w-4 flex-shrink-0" />
                                <span class="truncate">{{ branch.email }}</span>
                            </div>

                            <div v-if="branch.latitude && branch.longitude" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <MapPin class="h-4 w-4 flex-shrink-0" />
                                <a
                                    :href="`https://www.google.com/maps?q=${branch.latitude},${branch.longitude}`"
                                    target="_blank"
                                    class="truncate hover:text-purple-600 hover:underline dark:hover:text-purple-400"
                                >
                                    {{ parseFloat(String(branch.latitude)).toFixed(4) }}, {{ parseFloat(String(branch.longitude)).toFixed(4) }}
                                </a>
                            </div>
                            <div v-else class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
                                <MapPin class="h-4 w-4 flex-shrink-0" />
                                <span>لا يوجد موقع</span>
                            </div>
                        </div>

                        <!-- WhatsApp Alert Phones -->
                        <div class="mt-6 rounded-lg border border-green-200 bg-green-50/50 p-4 dark:border-green-800 dark:bg-green-900/10">
                            <div class="mb-3 flex items-center gap-2">
                                <MessageCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                                <Label class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    أرقام تنبيهات الواتساب
                                </Label>
                            </div>

                            <div class="space-y-2">
                                <div
                                    v-for="(phone, index) in branchForms[branch.id].phones"
                                    :key="`${branch.id}-phone-${index}`"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="branchForms[branch.id].phones[index]"
                                        type="tel"
                                        :placeholder="'+966501234567'"
                                        class="flex-1 bg-white dark:bg-gray-900"
                                    />
                                    <button
                                        type="button"
                                        @click="removePhoneField(branch.id, index)"
                                        class="rounded-lg border border-gray-300 px-2 text-gray-500 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="addPhoneField(branch.id)"
                                class="mt-2 inline-flex items-center gap-1 text-xs text-green-700 hover:underline dark:text-green-400"
                            >
                                <Plus class="h-3 w-3" />
                                <span>إضافة رقم</span>
                            </button>

                            <div class="mt-4 flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    class="flex-1"
                                    :disabled="savingBranchId === branch.id"
                                    @click="saveWhatsappPhones(branch.id)"
                                >
                                    {{ savingBranchId === branch.id ? 'جاري الحفظ...' : 'حفظ الأرقام' }}
                                </Button>
                            </div>

                            <div class="mt-4 border-t border-green-200 pt-4 dark:border-green-800">
                                <Label class="mb-2 block text-xs text-gray-600 dark:text-gray-400">
                                    إرسال رسالة تجريبية
                                </Label>
                                <div class="flex gap-2">
                                    <Input
                                        v-model="branchForms[branch.id].testPhone"
                                        type="tel"
                                        placeholder="رقم الاختبار"
                                        class="flex-1 bg-white dark:bg-gray-900"
                                    />
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="testingBranchId === branch.id"
                                        @click="sendTestMessage(branch.id)"
                                    >
                                        {{ testingBranchId === branch.id ? '...' : 'اختبار' }}
                                    </Button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    يُرسل قالب <code>new_kitchen_order_initiate</code>. اترك الحقل فارغاً لاستخدام أول رقم محفوظ.
                                </p>
                            </div>
                        </div>

                        <InputError v-if="whatsappTestError && testingBranchId === null" class="mt-2" :message="whatsappTestError" />

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
                </div>
            </div>
        </div>
    </AppLayout>
</template>
