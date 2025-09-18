<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, ShoppingCart, User, Store, DollarSign, Calendar } from 'lucide-vue-next';

interface Props {
    orders: Array<{
        id: number;
        order_number: string;
        status: string;
        total: number;
        items_count?: number;
        items_subtotal?: number;
        created_at: string;
        user: {
            name: string;
            email: string;
        };
        restaurant: {
            name: string;
        };
        order_items?: Array<{
            id: number;
            item_name: string;
            quantity: number;
            price: string;
            subtotal: string;
        }>;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Orders',
        href: '/orders',
    },
];

const getStatusColor = (status: string) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        preparing: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
        ready: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
        delivering: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400',
        delivered: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    };
    return colors[status as keyof typeof colors] || colors.pending;
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
    }).format(amount);
};
</script>

<template>
    <Head title="Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Orders</h1>
                    <p class="text-muted-foreground">Manage all customer orders</p>
                </div>
                <Link
                    :href="route('orders.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Create Order
                </Link>
            </div>

            <!-- Orders List -->
            <div class="space-y-4">
                <div v-for="order in orders" :key="order.id" class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900/20">
                                <ShoppingCart class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h3 class="font-semibold">{{ order.order_number }}</h3>
                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusColor(order.status)]">
                                        {{ order.status }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-muted-foreground">
                                    <div class="flex items-center space-x-1">
                                        <User class="h-4 w-4" />
                                        <span>{{ order.user.name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <Store class="h-4 w-4" />
                                        <span>{{ order.restaurant.name }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <ShoppingCart class="h-4 w-4" />
                                        <span>{{ order.items_count || 0 }} items</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <Calendar class="h-4 w-4" />
                                        <span>{{ new Date(order.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center space-x-2">
                                <DollarSign class="h-4 w-4 text-green-600" />
                                <span class="text-lg font-semibold text-green-600">{{ formatCurrency(order.total) }}</span>
                            </div>
                            <div class="mt-2 flex space-x-2">
                                <Link
                                    :href="route('orders.edit', order.id)"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    Edit
                                </Link>
                                <Link
                                    :href="route('orders.show', order.id)"
                                    class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700"
                                >
                                    View
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-12">
                <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">No orders found</h3>
                <p class="mt-2 text-muted-foreground">Orders will appear here once customers start placing them.</p>
                <Link
                    :href="route('orders.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Create Order
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
