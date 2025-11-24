<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();
</script>

<template>
    <SidebarGroup class="px-3 pt-4 pb-1 space-y-3">
        <!-- Menu label -->
        <SidebarGroupLabel class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">
            Menu
        </SidebarGroupLabel>

        <SidebarMenu class="space-y-2">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton as-child :is-active="item.href === page.url" class="p-0 bg-transparent">
                    <Link
                        :href="item.href"
                        :class="[
                            'flex items-center justify-between w-full rounded-2xl px-2.5 py-2 text-sm font-medium transition-colors',
                            item.href === page.url
                                ? 'bg-[#cf4823] text-white shadow-sm'
                                : 'text-gray-500 hover:bg-gray-100'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'flex h-9 w-9 items-center justify-center rounded-xl transition-colors',
                                    item.href === page.url
                                        ? 'bg-white text-[#ce4622] shadow-sm'
                                        : 'bg-gray-100 text-[#ce4622]'
                                ]"
                            >
                                <component :is="item.icon" class="h-4 w-4" />
                            </div>
                            <span class="truncate">
                                {{ item.title }}
                            </span>
                        </div>

                        <span
                            v-if="item.badge && item.badge > 0"
                            class="inline-flex items-center justify-center rounded-full bg-purple-500 px-2 py-0.5 text-[10px] font-semibold text-white"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
