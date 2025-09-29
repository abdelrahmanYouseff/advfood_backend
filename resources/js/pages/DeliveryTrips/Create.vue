<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Truck, User, Phone, MapPin, ShoppingCart } from 'lucide-vue-next';
import { ref } from 'vue';

interface Order {
    id: number;
    order_number: string;
    user: { name: string };
    restaurant: { name: string };
    total: number;
    delivery_address: string;
    delivery_phone: string;
}

interface Props {
    availableOrders: Order[];
}

const props = defineProps<Props>();

const selectedOrders = ref<number[]>([]);

const form = useForm({
    driver_name: '',
    driver_phone: '',
    vehicle_type: 'car',
    vehicle_number: '',
    selected_orders: [] as number[],
    notes: '',
});

const vehicleTypes = [
    { value: 'bike', label: 'دراجة' },
    { value: 'car', label: 'سيارة' },
    { value: 'truck', label: 'شاحنة' },
    { value: 'motorcycle', label: 'دراجة نارية' },
];

const toggleOrderSelection = (orderId: number) => {
    const index = selectedOrders.value.indexOf(orderId);
    if (index > -1) {
        selectedOrders.value.splice(index, 1);
    } else {
        selectedOrders.value.push(orderId);
    }
    form.selected_orders = selectedOrders.value;
};

const submit = () => {
    form.post(route('delivery-trips.store'));
};
</script>

<template>
    <Head title="إنشاء رحلة توصيل جديدة" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <Link :href="route('delivery-trips.index')" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                    <ArrowLeft class="h-4 w-4" />
                    العودة للرحلات
                </Link>
                <div>
                    <h1 class="text-2xl font-bold">إنشاء رحلة توصيل جديدة</h1>
                    <p class="text-muted-foreground">إضافة رحلة توصيل جديدة مع اختيار الطلبات</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Driver Information -->
                <div class="space-y-4">
                    <div class="rounded-lg border bg-card p-6">
                        <h3 class="mb-4 flex items-center gap-2 font-semibold">
                            <User class="h-5 w-5" />
                            معلومات السائق
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">اسم السائق</label>
                                <input
                                    v-model="form.driver_name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="أدخل اسم السائق"
                                    required
                                />
                                <div v-if="form.errors.driver_name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.driver_name }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">هاتف السائق</label>
                                <input
                                    v-model="form.driver_phone"
                                    type="tel"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="أدخل رقم الهاتف"
                                    required
                                />
                                <div v-if="form.errors.driver_phone" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.driver_phone }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Information -->
                    <div class="rounded-lg border bg-card p-6">
                        <h3 class="mb-4 flex items-center gap-2 font-semibold">
                            <Truck class="h-5 w-5" />
                            معلومات المركبة
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">نوع المركبة</label>
                                <select
                                    v-model="form.vehicle_type"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                >
                                    <option v-for="type in vehicleTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                                <div v-if="form.errors.vehicle_type" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.vehicle_type }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2">رقم المركبة</label>
                                <input
                                    v-model="form.vehicle_number"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="أدخل رقم المركبة"
                                    required
                                />
                                <div v-if="form.errors.vehicle_number" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.vehicle_number }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="rounded-lg border bg-card p-6">
                        <h3 class="mb-4 font-semibold">ملاحظات</h3>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="أدخل أي ملاحظات إضافية..."
                        ></textarea>
                        <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                            {{ form.errors.notes }}
                        </div>
                    </div>
                </div>

                <!-- Orders Selection -->
                <div class="space-y-4">
                    <div class="rounded-lg border bg-card p-6">
                        <h3 class="mb-4 flex items-center gap-2 font-semibold">
                            <ShoppingCart class="h-5 w-5" />
                            اختيار الطلبات
                        </h3>

                        <div v-if="props.availableOrders.length === 0" class="text-center py-8">
                            <MapPin class="mx-auto h-12 w-12 text-muted-foreground" />
                            <h4 class="mt-4 text-lg font-semibold">لا توجد طلبات متاحة</h4>
                            <p class="mt-2 text-muted-foreground">جميع الطلبات الجاهزة مخصصة لرحلات أخرى</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="order in props.availableOrders" :key="order.id"
                                 :class="['cursor-pointer rounded-lg border p-4 transition-colors',
                                          selectedOrders.includes(order.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300']"
                                 @click="toggleOrderSelection(order.id)">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <input
                                            type="checkbox"
                                            :checked="selectedOrders.includes(order.id)"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            @change="toggleOrderSelection(order.id)"
                                        />
                                        <div>
                                            <p class="font-medium">{{ order.order_number }}</p>
                                            <p class="text-sm text-muted-foreground">{{ order.user.name }} - {{ order.restaurant.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ order.delivery_address }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium">{{ order.total }} SAR</p>
                                        <p class="text-sm text-muted-foreground">{{ order.delivery_phone }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.selected_orders" class="mt-2 text-sm text-red-600">
                            {{ form.errors.selected_orders }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end space-x-4">
                <Link :href="route('delivery-trips.index')" class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing || selectedOrders.length === 0"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء الرحلة' }}
                </button>
            </div>
        </form>
    </div>
</template>
