<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    Users,
    Store,
    ShoppingCart,
    DollarSign,
    Clock,
    TrendingUp,
    Package,
    Calendar
} from 'lucide-vue-next';

interface Props {
    stats: {
        total_users: number;
        total_restaurants: number;
        total_orders: number;
        total_revenue: number;
        pending_orders: number;
        today_orders: number;
        today_revenue: number;
    };
    recent_orders: Array<{
        id: number;
        order_number: string;
        status: string;
        total: number;
        created_at: string;
        user: {
            name: string;
            email: string;
        };
        restaurant: {
            name: string;
        };
    }>;
    top_restaurants: Array<{
        id: number;
        name: string;
        orders_count: number;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
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
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Users</p>
                            <p class="text-2xl font-bold">{{ stats.total_users }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                            <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Restaurants</p>
                            <p class="text-2xl font-bold">{{ stats.total_restaurants }}</p>
                        </div>
                        <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900/20">
                            <Store class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Orders</p>
                            <p class="text-2xl font-bold">{{ stats.total_orders }}</p>
                        </div>
                        <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900/20">
                            <ShoppingCart class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Revenue</p>
                            <p class="text-2xl font-bold">{{ formatCurrency(stats.total_revenue) }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-100 p-3 dark:bg-emerald-900/20">
                            <DollarSign class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending Orders</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ stats.pending_orders }}</p>
                        </div>
                        <div class="rounded-lg bg-yellow-100 p-3 dark:bg-yellow-900/20">
                            <Clock class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Today's Orders</p>
                            <p class="text-2xl font-bold">{{ stats.today_orders }}</p>
                        </div>
                        <div class="rounded-lg bg-indigo-100 p-3 dark:bg-indigo-900/20">
                            <Calendar class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Today's Revenue</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ formatCurrency(stats.today_revenue) }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-100 p-3 dark:bg-emerald-900/20">
                            <TrendingUp class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders and Top Restaurants -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Recent Orders -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Recent Orders</h3>
                        <Link
                            :href="route('orders.index')"
                            class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="order in recent_orders" :key="order.id" class="flex items-center justify-between rounded-lg border p-4">
                            <div class="flex items-center space-x-3">
                                <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/20">
                                    <Package class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p class="font-medium">{{ order.order_number }}</p>
                                    <p class="text-sm text-muted-foreground">{{ order.user.name }} • {{ order.restaurant.name }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">{{ formatCurrency(order.total) }}</p>
                                <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', getStatusColor(order.status)]">
                                    {{ order.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Restaurants -->
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Top Restaurants</h3>
                        <Link
                            :href="route('restaurants.index')"
                            class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="space-y-4">
                        <div v-for="(restaurant, index) in top_restaurants" :key="restaurant.id" class="flex items-center justify-between rounded-lg border p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <p class="font-medium">{{ restaurant.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ restaurant.orders_count }} orders</p>
                                </div>
                            </div>
                            <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/20">
                                <Store class="h-4 w-4 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
