<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Users,
    Store,
    ShoppingCart,
    FileText,
    Menu,
    Tag,
    Settings,
    Megaphone,
    Link as LinkIcon,
    Truck
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();

// Get pending orders count from page props
const pendingOrdersCount = computed(() => {
    const orders = page.props.orders as any;
    if (!orders) return 0;

    // Handle both array and pagination object
    const ordersArray = Array.isArray(orders) ? orders : (orders.data || []);

    // Count orders with pending status
    return ordersArray.filter((order: any) => order.status === 'pending').length;
});

// Get pending link orders count from page props
const pendingLinkOrdersCount = computed(() => {
    const linkOrders = page.props.linkOrders as any;
    if (!linkOrders) return 0;

    // Handle both array and pagination object
    const linkOrdersArray = Array.isArray(linkOrders) ? linkOrders : (linkOrders.data || []);

    // Count link orders with pending status
    return linkOrdersArray.filter((order: any) => order.status === 'pending').length;
});

const mainNavItems = computed(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: '/users',
        icon: Users,
    },
    {
        title: 'Restaurants',
        href: '/restaurants',
        icon: Store,
    },
    {
        title: 'Menu Items',
        href: '/menu-items',
        icon: Menu,
    },
    {
        title: 'Orders',
        href: '/orders',
        icon: ShoppingCart,
        badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
    },
    {
        title: 'Invoices',
        href: '/invoices',
        icon: FileText,
    },
    {
        title: 'Ads',
        href: '/ads',
        icon: Megaphone,
    },
    {
        title: 'Link Orders',
        href: '/link-orders',
        icon: LinkIcon,
        badge: pendingLinkOrdersCount.value > 0 ? pendingLinkOrdersCount.value : undefined,
    },
    {
        title: 'Delivery Trips',
        href: '/delivery-trips',
        icon: Truck,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
