<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Truck, User, Phone, MapPin, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface DeliveryTrip {
    id: number;
    trip_number: string;
    driver_name: string;
    driver_phone: string;
    vehicle_type: string;
    vehicle_number: string;
    status: string;
    started_at: string | null;
    completed_at: string | null;
    notes: string | null;
    total_distance: number | null;
    fuel_cost: number | null;
    driver_fee: number | null;
    total_cost: number | null;
    created_at: string;
    orders: Array<{
        id: number;
        order_number: string;
        user: { name: string };
        restaurant: { name: string };
        total: number;
        delivery_address: string;
        delivery_phone: string;
        pivot: {
            sequence_order: number;
            delivery_status: string;
            picked_up_at: string | null;
            delivered_at: string | null;
            delivery_notes: string | null;
            delivery_fee: number | null;
        };
    }>;
}

interface Props {
    deliveryTrips: DeliveryTrip[];
}

const props = defineProps<Props>();

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'pending':
            return 'معلق';
        case 'in_progress':
            return 'قيد التنفيذ';
        case 'completed':
            return 'مكتمل';
        case 'cancelled':
            return 'ملغي';
        default:
            return status;
    }
};

const getVehicleTypeText = (type: string) => {
    switch (type) {
        case 'bike':
            return 'دراجة';
        case 'car':
            return 'سيارة';
        case 'truck':
            return 'شاحنة';
        case 'motorcycle':
            return 'دراجة نارية';
        default:
            return type;
    }
};

const getDeliveryStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'picked_up':
            return 'bg-blue-100 text-blue-800';
        case 'delivered':
            return 'bg-green-100 text-green-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getDeliveryStatusText = (status: string) => {
    switch (status) {
        case 'pending':
            return 'معلق';
        case 'picked_up':
            return 'تم الاستلام';
        case 'delivered':
            return 'تم التسليم';
        case 'failed':
            return 'فشل التسليم';
        default:
            return status;
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const startTrip = (tripId: number) => {
    router.patch(route('delivery-trips.start', tripId), {}, {
        onSuccess: () => {
            // Trip started successfully
        }
    });
};

const completeTrip = (tripId: number) => {
    router.patch(route('delivery-trips.complete', tripId), {}, {
        onSuccess: () => {
            // Trip completed successfully
        }
    });
};

const updateOrderStatus = (tripId: number, orderId: number, status: string) => {
    router.patch(route('delivery-trips.update-order-status', [tripId, orderId]), {
        delivery_status: status
    }, {
        onSuccess: () => {
            // Order status updated successfully
        }
    });
};
</script>

<template>
    <Head title="رحلات التوصيل" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">رحلات التوصيل</h1>
                <p class="text-muted-foreground">إدارة رحلات التوصيل من شركة اللوجيستيك</p>
            </div>
            <Link :href="route('delivery-trips.create')" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                <Plus class="h-4 w-4" />
                إنشاء رحلة جديدة
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border bg-card p-6">
                <div class="flex items-center space-x-2">
                    <Truck class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">إجمالي الرحلات</span>
                </div>
                <div class="text-2xl font-bold">{{ props.deliveryTrips.length }}</div>
            </div>
            <div class="rounded-lg border bg-card p-6">
                <div class="flex items-center space-x-2">
                    <Clock class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">معلقة</span>
                </div>
                <div class="text-2xl font-bold">{{ props.deliveryTrips.filter(trip => trip.status === 'pending').length }}</div>
            </div>
            <div class="rounded-lg border bg-card p-6">
                <div class="flex items-center space-x-2">
                    <AlertCircle class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">قيد التنفيذ</span>
                </div>
                <div class="text-2xl font-bold">{{ props.deliveryTrips.filter(trip => trip.status === 'in_progress').length }}</div>
            </div>
            <div class="rounded-lg border bg-card p-6">
                <div class="flex items-center space-x-2">
                    <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">مكتملة</span>
                </div>
                <div class="text-2xl font-bold">{{ props.deliveryTrips.filter(trip => trip.status === 'completed').length }}</div>
            </div>
        </div>

        <!-- Delivery Trips List -->
        <div class="space-y-4">
            <div v-for="trip in props.deliveryTrips" :key="trip.id" class="rounded-lg border bg-card">
                <!-- Trip Header -->
                <div class="flex items-center justify-between p-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                            <Truck class="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{{ trip.trip_number }}</h3>
                            <p class="text-sm text-muted-foreground">{{ trip.driver_name }} - {{ getVehicleTypeText(trip.vehicle_type) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getStatusColor(trip.status)]">
                            {{ getStatusText(trip.status) }}
                        </span>
                        <div class="flex space-x-1">
                            <Link :href="route('delivery-trips.show', trip.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700">
                                عرض
                            </Link>
                            <Link :href="route('delivery-trips.edit', trip.id)" class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                تعديل
                            </Link>
                            <button v-if="trip.status === 'pending'" @click="startTrip(trip.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors bg-green-600 text-white hover:bg-green-700">
                                بدء الرحلة
                            </button>
                            <button v-if="trip.status === 'in_progress'" @click="completeTrip(trip.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors bg-orange-600 text-white hover:bg-orange-700">
                                إكمال الرحلة
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Trip Details -->
                <div class="border-t p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">السائق:</span>
                                <span class="text-sm">{{ trip.driver_name }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Phone class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">الهاتف:</span>
                                <span class="text-sm">{{ trip.driver_phone }}</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Truck class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">المركبة:</span>
                                <span class="text-sm">{{ getVehicleTypeText(trip.vehicle_type) }} - {{ trip.vehicle_number }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <MapPin class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-medium">عدد الطلبات:</span>
                                <span class="text-sm">{{ trip.orders.length }}</span>
                            </div>
                            <div v-if="trip.total_distance" class="flex items-center space-x-2">
                                <span class="text-sm font-medium">المسافة:</span>
                                <span class="text-sm">{{ trip.total_distance }} كم</span>
                            </div>
                            <div v-if="trip.total_cost" class="flex items-center space-x-2">
                                <span class="text-sm font-medium">التكلفة:</span>
                                <span class="text-sm">{{ trip.total_cost }} SAR</span>
                            </div>
                        </div>
                    </div>

                    <!-- Orders in this trip -->
                    <div v-if="trip.orders.length > 0" class="mt-4">
                        <h4 class="mb-3 font-medium">الطلبات في هذه الرحلة:</h4>
                        <div class="space-y-2">
                            <div v-for="order in trip.orders" :key="order.id" class="flex items-center justify-between rounded-lg border p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                        <span class="text-xs font-medium">{{ order.pivot.sequence_order }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ order.order_number }}</p>
                                        <p class="text-sm text-muted-foreground">{{ order.user.name }} - {{ order.restaurant.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ order.delivery_address }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span :class="['inline-flex items-center rounded-full px-2 py-1 text-xs font-medium', getDeliveryStatusColor(order.pivot.delivery_status)]">
                                        {{ getDeliveryStatusText(order.pivot.delivery_status) }}
                                    </span>
                                    <div class="flex space-x-1">
                                        <button v-if="order.pivot.delivery_status === 'pending'" @click="updateOrderStatus(trip.id, order.id, 'picked_up')" class="rounded-lg px-2 py-1 text-xs font-medium transition-colors bg-blue-600 text-white hover:bg-blue-700">
                                            استلام
                                        </button>
                                        <button v-if="order.pivot.delivery_status === 'picked_up'" @click="updateOrderStatus(trip.id, order.id, 'delivered')" class="rounded-lg px-2 py-1 text-xs font-medium transition-colors bg-green-600 text-white hover:bg-green-700">
                                            تسليم
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="props.deliveryTrips.length === 0" class="flex flex-col items-center justify-center py-12">
            <Truck class="h-12 w-12 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-semibold">لا توجد رحلات توصيل</h3>
            <p class="mt-2 text-muted-foreground">ابدأ بإنشاء أول رحلة توصيل.</p>
            <Link :href="route('delivery-trips.create')" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                <Plus class="h-4 w-4" />
                إنشاء رحلة جديدة
            </Link>
        </div>
    </div>
</template>
