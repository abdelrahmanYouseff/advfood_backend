<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft,
    Clock, 
    CheckCircle, 
    ChefHat, 
    Truck, 
    XCircle,
    Calendar,
    User,
    Phone,
    MapPin,
    DollarSign,
    Building,
    Layers,
    DoorOpen,
    Navigation,
    StickyNote,
    ShoppingCart,
    Utensils
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Order {
    id: number;
    restaurant_id: number;
    status: string;
    full_name: string;
    phone_number: string;
    building_no: string;
    floor: string;
    apartment_number: string;
    street: string;
    note?: string;
    total: number;
    cart_items: Array<{
        id: number;
        name: string;
        quantity: number;
        price: number;
        description?: string;
    }>;
    created_at: string;
    updated_at: string;
    restaurant: {
        id: number;
        name: string;
    };
}

interface Props {
    order: Order;
}

const props = defineProps<Props>();

const newStatus = ref(props.order.status);

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'pending': return Clock;
        case 'confirmed': return CheckCircle;
        case 'preparing': return ChefHat;
        case 'ready': return CheckCircle;
        case 'delivered': return Truck;
        case 'cancelled': return XCircle;
        default: return Clock;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'text-yellow-600 bg-yellow-100';
        case 'confirmed': return 'text-blue-600 bg-blue-100';
        case 'preparing': return 'text-orange-600 bg-orange-100';
        case 'ready': return 'text-green-600 bg-green-100';
        case 'delivered': return 'text-emerald-600 bg-emerald-100';
        case 'cancelled': return 'text-red-600 bg-red-100';
        default: return 'text-gray-600 bg-gray-100';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'pending': return 'Pending';
        case 'confirmed': return 'Confirmed';
        case 'preparing': return 'Preparing';
        case 'ready': return 'Ready';
        case 'delivered': return 'Delivered';
        case 'cancelled': return 'Cancelled';
        default: return status;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const updateStatus = () => {
    router.post(route('link-orders.update-status', props.order.id), {
        status: newStatus.value
    });
};

const breadcrumbs = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Link Orders', href: '/link-orders' },
    { name: `Order #${props.order.id.toString().padStart(4, '0')}`, href: '#' }
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Order #${order.id.toString().padStart(4, '0')}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('link-orders.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to Orders
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold">Order #{{ order.id.toString().padStart(4, '0') }}</h1>
                        <p class="text-muted-foreground">{{ order.restaurant.name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="['inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium', getStatusColor(order.status)]">
                        <component :is="getStatusIcon(order.status)" class="h-4 w-4" />
                        {{ getStatusText(order.status) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Order Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Information -->
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <User class="h-5 w-5" />
                            Customer Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Full Name</label>
                                <p class="text-sm font-medium">{{ order.full_name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Phone Number</label>
                                <p class="text-sm font-medium flex items-center gap-1">
                                    <Phone class="h-4 w-4" />
                                    {{ order.phone_number }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Delivery Address
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Building No</label>
                                <p class="text-sm font-medium flex items-center gap-1">
                                    <Building class="h-4 w-4" />
                                    {{ order.building_no }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Floor</label>
                                <p class="text-sm font-medium flex items-center gap-1">
                                    <Layers class="h-4 w-4" />
                                    {{ order.floor }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Apartment Number</label>
                                <p class="text-sm font-medium flex items-center gap-1">
                                    <DoorOpen class="h-4 w-4" />
                                    {{ order.apartment_number }}
                                </p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Street</label>
                                <p class="text-sm font-medium flex items-center gap-1">
                                    <Navigation class="h-4 w-4" />
                                    {{ order.street }}
                                </p>
                            </div>
                        </div>
                        <div v-if="order.note" class="mt-4">
                            <label class="text-sm font-medium text-muted-foreground">Note</label>
                            <p class="text-sm font-medium flex items-start gap-1">
                                <StickyNote class="h-4 w-4 mt-0.5" />
                                {{ order.note }}
                            </p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <ShoppingCart class="h-5 w-5" />
                            Order Items
                        </h2>
                        <div class="space-y-3">
                            <div
                                v-for="item in order.cart_items"
                                :key="item.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                                        <Utensils class="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <h3 class="font-medium">{{ item.name }}</h3>
                                        <p v-if="item.description" class="text-sm text-muted-foreground">{{ item.description }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium">Qty: {{ item.quantity }}</p>
                                    <p class="text-sm text-muted-foreground">{{ ((item.price || 0) * (item.quantity || 0)).toFixed(2) }} رس</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="space-y-6">
                    <!-- Status Update -->
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="text-lg font-semibold mb-4">Update Status</h2>
                        <div class="space-y-3">
                            <select
                                v-model="newStatus"
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="preparing">Preparing</option>
                                <option value="ready">Ready</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <button
                                @click="updateStatus"
                                :disabled="newStatus === order.status"
                                class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Update Status
                            </button>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Order ID</span>
                                <span class="font-medium">#{{ order.id.toString().padStart(4, '0') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Restaurant</span>
                                <span class="font-medium">{{ order.restaurant.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Items</span>
                                <span class="font-medium">{{ order.cart_items.length }} items</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total Quantity</span>
                                <span class="font-medium">{{ order.cart_items.reduce((total, item) => total + item.quantity, 0) }}</span>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex justify-between text-lg font-semibold">
                                    <span>Total</span>
                                    <span class="flex items-center gap-1">
                                        <DollarSign class="h-4 w-4" />
                                        {{ (order.total || 0).toFixed(2) }} رس
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Timeline -->
                    <div class="rounded-lg border bg-card p-6">
                        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Order Timeline
                        </h2>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 bg-primary rounded-full"></div>
                                <div>
                                    <p class="text-sm font-medium">Order Placed</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="order.updated_at !== order.created_at" class="flex items-center gap-3">
                                <div class="w-2 h-2 bg-muted-foreground rounded-full"></div>
                                <div>
                                    <p class="text-sm font-medium">Last Updated</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(order.updated_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
