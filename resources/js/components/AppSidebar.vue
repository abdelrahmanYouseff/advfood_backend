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
import { computed, ref, watch } from 'vue';

const page = usePage();

// Sidebar language state (العربية / English)
const sidebarLang = ref<'ar' | 'en'>('ar');

if (typeof window !== 'undefined') {
    const storedLang = window.localStorage.getItem('sidebarLang');
    if (storedLang === 'en' || storedLang === 'ar') {
        sidebarLang.value = storedLang;
    }
}

watch(sidebarLang, (value) => {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem('sidebarLang', value);
        // Notify other components (like Dashboard) about language change
        window.dispatchEvent(new CustomEvent('sidebar-lang-changed', { detail: value }));
    }
});

// Helper for localized titles
const t = (ar: string, en: string) => (sidebarLang.value === 'ar' ? ar : en);

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

    // Check if user is acc@adv-line.sa (Invoice viewer only)
    const isInvoiceViewer = userEmail === 'acc@adv-line.sa';

    // Check if user is admin2@advfood.com
    const isAdmin2 = userEmail === 'admin2@advfood.com';

    // Check if user is admin@advfood.com
    const isAdmin = userEmail === 'admin@advfood.com';

    if (isInvoiceViewer) {
        // Only invoices menu for acc@adv-line.sa
        return [
            {
                title: t('لوحة التحكم', 'Dashboard'),
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: t('الفواتير', 'Invoices'),
                href: '/invoices',
                icon: FileText,
            },
        ];
    }

    if (isAdmin2) {
        // Limited menu for admin2@advfood.com
        return [
            {
                title: t('لوحة التحكم', 'Dashboard'),
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: t('الطلبات', 'Orders'),
                href: '/orders',
                icon: ShoppingCart,
                badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
            },
            {
                title: t('طلبات Zyda', 'Zyda Orders'),
                href: '/dashboard?tab=zyda',
                icon: Package,
            },
            {
                title: t('الفواتير', 'Invoices'),
                href: '/invoices',
                icon: FileText,
            },
            {
                title: t('العملاء', 'Customers'),
                href: '/online-customers',
                icon: UserCircle2,
            },
        ];
    }

    if (isAdmin) {
        // Full menu in Arabic for admin@advfood.com
        return [
            {
                title: t('لوحة التحكم', 'Dashboard'),
                href: '/dashboard',
                icon: LayoutGrid,
            },
            {
                title: t('المستخدمين', 'Users'),
                href: '/users',
                icon: Users,
            },
            {
                title: t('المطاعم', 'Restaurants'),
                href: '/restaurants',
                icon: Store,
            },
            {
                title: t('عناصر القائمة', 'Menu Items'),
                href: '/menu-items',
                icon: Menu,
            },
            {
                title: t('الطلبات', 'Orders'),
                href: '/orders',
                icon: ShoppingCart,
                badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
            },
            {
                title: t('طلبات Zyda', 'Zyda Orders'),
                href: '/dashboard?tab=zyda',
                icon: Package,
            },
            {
                title: t('الفواتير', 'Invoices'),
                href: '/invoices',
                icon: FileText,
            },
            {
                title: t('العملاء', 'Customers'),
                href: '/online-customers',
                icon: UserCircle2,
            },
            {
                title: t('الإعلانات', 'Ads'),
                href: '/ads',
                icon: Megaphone,
            },
        ];
    }

    // Full menu for other users
    return [
        {
            title: t('لوحة التحكم', 'Dashboard'),
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: t('المستخدمين', 'Users'),
            href: '/users',
            icon: Users,
        },
        {
            title: t('المطاعم', 'Restaurants'),
            href: '/restaurants',
            icon: Store,
        },
        {
            title: t('عناصر القائمة', 'Menu Items'),
            href: '/menu-items',
            icon: Menu,
        },
        {
            title: t('الطلبات', 'Orders'),
            href: '/orders',
            icon: ShoppingCart,
            badge: pendingOrdersCount.value > 0 ? pendingOrdersCount.value : undefined,
        },
        {
            title: t('طلبات Zyda', 'Zyda Orders'),
            href: '/dashboard?tab=zyda',
            icon: Package,
        },
        {
            title: t('الفواتير', 'Invoices'),
            href: '/invoices',
            icon: FileText,
        },
        {
            title: t('العملاء', 'Customers'),
            href: '/online-customers',
            icon: UserCircle2,
        },
        {
            title: t('الإعلانات', 'Ads'),
            href: '/ads',
            icon: Megaphone,
        },
    ];
});

const footerNavItems = computed(() => {
    const user = page.props.auth?.user;
    const userEmail = user?.email;
    
    // Hide settings for acc@adv-line.sa
    if (userEmail === 'acc@adv-line.sa') {
        return [];
    }
    
    return [
        {
            title: t('الإعدادات', 'Settings'),
            href: '/settings/profile',
            icon: Settings,
        },
    ];
});
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
            <!-- Language Toggle -->
            <div class="mb-3 flex items-center justify-center gap-1 rounded-full bg-gray-100 px-1 py-1 text-xs font-medium text-gray-700">
                <button
                    type="button"
                    @click="sidebarLang = 'ar'"
                    :class="[
                        'rounded-full px-3 py-1 transition',
                        sidebarLang === 'ar' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:bg-white/60'
                    ]"
                >
                    العربية
                </button>
                <button
                    type="button"
                    @click="sidebarLang = 'en'"
                    :class="[
                        'rounded-full px-3 py-1 transition',
                        sidebarLang === 'en' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:bg-white/60'
                    ]"
                >
                    English
                </button>
            </div>

            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
