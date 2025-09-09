<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ShoppingCart,
    Eye,
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
    Filter,
    Search
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

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
    restaurant: {
        id: number;
        name: string;
    };
}

interface Props {
    orders: {
        data: Order[];
        links: any[];
        meta: {
            total: number;
            from: number;
            to: number;
            current_page: number;
            last_page: number;
            per_page: number;
        };
    };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const statusFilter = ref('all');

const filteredOrders = computed(() => {
    let filtered = props.orders?.data || [];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(order =>
            order.full_name.toLowerCase().includes(query) ||
            order.phone_number.includes(query) ||
            order.restaurant?.name.toLowerCase().includes(query) ||
            order.id.toString().includes(query)
        );
    }

    if (statusFilter.value !== 'all') {
        filtered = filtered.filter(order => order.status === statusFilter.value);
    }

    return filtered;
});

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
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTotalItems = (cartItems: any[]) => {
    return cartItems.reduce((total, item) => total + item.quantity, 0);
};

const updateOrderStatus = (orderId: number, newStatus: string) => {
    router.post(route('link-orders.update-status', orderId), {
        status: newStatus
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Update the local data
            const order = props.orders?.data?.find(o => o.id === orderId);
            if (order) {
                order.status = newStatus;
            }
        }
    });
};

const breadcrumbs = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Link Orders', href: '/link-orders' }
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Link Orders" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Link Orders</h1>
                    <p class="text-muted-foreground">Manage orders from restaurant links</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ orders?.meta?.total || 0 }} orders
                    </span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <Clock class="h-5 w-5 text-yellow-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending</p>
                            <p class="text-2xl font-bold">{{ (orders?.data || []).filter(o => o.status === 'pending').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <ChefHat class="h-5 w-5 text-orange-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Preparing</p>
                            <p class="text-2xl font-bold">{{ (orders?.data || []).filter(o => o.status === 'preparing').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <CheckCircle class="h-5 w-5 text-green-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Ready</p>
                            <p class="text-2xl font-bold">{{ (orders?.data || []).filter(o => o.status === 'ready').length }}</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="flex items-center gap-2">
                        <Truck class="h-5 w-5 text-emerald-600" />
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Delivered</p>
                            <p class="text-2xl font-bold">{{ (orders?.data || []).filter(o => o.status === 'delivered').length }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by name, phone, restaurant, or order ID..."
                        class="pl-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <Filter class="h-4 w-4 text-muted-foreground" />
                    <select
                        v-model="statusFilter"
                        class="rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="preparing">Preparing</option>
                        <option value="ready">Ready</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="rounded-lg border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px]">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Order ID</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Restaurant</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Customer</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Items</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Total</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date</th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in filteredOrders" :key="order.id" class="border-b transition-colors hover:bg-muted/50">
                                <td class="p-4 align-middle">
                                    <div class="font-medium">#{{ order.id.toString().padStart(4, '0') }}</div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="font-medium">{{ order.restaurant.name }}</div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <User class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">{{ order.full_name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Phone class="h-3 w-3" />
                                            <span>{{ order.phone_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="text-sm">
                                        {{ getTotalItems(order.cart_items) }} items
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-1 font-medium">
                                        <DollarSign class="h-4 w-4" />
                                        {{ (order.total || 0).toFixed(2) }} رس
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <span :class="['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium', getStatusColor(order.status)]">
                                        <component :is="getStatusIcon(order.status)" class="h-3 w-3" />
                                        {{ getStatusText(order.status) }}
                                    </span>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(order.created_at) }}
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <Link
                                            :href="route('link-orders.show', order.id)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700"
                                        >
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Link>
                                        <select
                                            :value="order.status"
                                            @change="updateOrderStatus(order.id, $event.target.value)"
                                            class="text-xs rounded border border-input bg-background px-2 py-1"
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="preparing">Preparing</option>
                                            <option value="ready">Ready</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="filteredOrders.length === 0" class="p-8 text-center">
                    <ShoppingCart class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <h3 class="text-lg font-semibold mb-2">No orders found</h3>
                    <p class="text-muted-foreground">
                        {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your search or filter criteria.' : 'No orders have been placed yet.' }}
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="orders?.links && orders.links.length > 3" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ orders?.meta?.from || 0 }} to {{ orders?.meta?.to || 0 }} of {{ orders?.meta?.total || 0 }} results
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in orders.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            'px-3 py-2 text-sm rounded-lg border',
                            link.active
                                ? 'bg-primary text-primary-foreground border-primary'
                                : 'bg-background text-foreground border-input hover:bg-muted'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
