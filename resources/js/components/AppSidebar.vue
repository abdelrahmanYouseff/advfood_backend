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
    Truck,
    UserCircle2,
    Package
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

const mainNavItems = computed(() => {
    const user = page.props.auth?.user;
    const userEmail = user?.email;

    // Check if user is admin2@advfood.com
    const isAdmin2 = userEmail === 'admin2@advfood.com';

    // Check if user is admin@advfood.com
    const isAdmin = userEmail === 'admin@advfood.com';

    if (isAdmin2) {
        // Limited menu for admin2@advfood.com
        return [
            {
                title: 'لوحة التحكم',
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'الطلبات',
                href: '/orders',
                icon: ShoppingCart,
                badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
            },
            {
                title: 'Zyda Orders',
                href: '/dashboard?tab=zyda',
                icon: Package,
            },
            {
                title: 'طلبات الروابط',
                href: '/link-orders',
                icon: LinkIcon,
                badge: pendingLinkOrdersCount.value > 0 ? pendingLinkOrdersCount.value : undefined,
            },
            {
                title: 'الفواتير',
                href: '/invoices',
                icon: FileText,
            },
            {
                title: 'العملاء',
                href: '/online-customers',
                icon: UserCircle2,
            },
        ];
    }

    if (isAdmin) {
        // Full menu in Arabic for admin@advfood.com
        return [
            {
                title: 'لوحة التحكم',
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'المستخدمين',
                href: '/users',
                icon: Users,
            },
            {
                title: 'المطاعم',
                href: '/restaurants',
                icon: Store,
            },
            {
                title: 'عناصر القائمة',
                href: '/menu-items',
                icon: Menu,
            },
            {
                title: 'الطلبات',
                href: '/orders',
                icon: ShoppingCart,
                badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
            },
            {
                title: 'Zyda Orders',
                href: '/dashboard?tab=zyda',
                icon: Package,
            },
            {
                title: 'الفواتير',
                href: '/invoices',
                icon: FileText,
            },
            {
                title: 'العملاء',
                href: '/online-customers',
                icon: UserCircle2,
            },
            {
                title: 'الإعلانات',
                href: '/ads',
                icon: Megaphone,
            },
            {
                title: 'طلبات الروابط',
                href: '/link-orders',
                icon: LinkIcon,
                badge: pendingLinkOrdersCount.value > 0 ? pendingLinkOrdersCount.value : undefined,
            },
        ];
    }

    // Full menu for other users
    return [
        {
            title: 'لوحة التحكم',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'المستخدمين',
            href: '/users',
            icon: Users,
        },
        {
            title: 'المطاعم',
            href: '/restaurants',
            icon: Store,
        },
        {
            title: 'عناصر القائمة',
            href: '/menu-items',
            icon: Menu,
        },
        {
            title: 'الطلبات',
            href: '/orders',
            icon: ShoppingCart,
            badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
        },
        {
            title: 'Zyda Orders',
            href: '/dashboard?tab=zyda',
            icon: Package,
        },
        {
            title: 'الفواتير',
            href: '/invoices',
            icon: FileText,
        },
        {
            title: 'العملاء',
            href: '/online-customers',
            icon: UserCircle2,
        },
        {
            title: 'الإعلانات',
            href: '/ads',
            icon: Megaphone,
        },
        {
            title: 'طلبات الروابط',
            href: '/link-orders',
            icon: LinkIcon,
            badge: pendingLinkOrdersCount.value > 0 ? pendingLinkOrdersCount.value : undefined,
        },
    ];
});

const footerNavItems: NavItem[] = [
    {
        title: 'الإعدادات',
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
