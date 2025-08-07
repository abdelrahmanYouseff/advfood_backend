<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Users, Mail, Calendar } from 'lucide-vue-next';

interface Props {
    users: Array<{
        id: number;
        name: string;
        email: string;
        created_at: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Users',
        href: '/users',
    },
];
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Users</h1>
                    <p class="text-muted-foreground">Manage all registered users</p>
                </div>
                <Link
                    :href="route('users.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Add User
                </Link>
            </div>

            <!-- Users Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="user in users" :key="user.id" class="rounded-xl border bg-card p-6 shadow-sm">
                    <div class="flex items-center space-x-4">
                        <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900/20">
                            <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold">{{ user.name }}</h3>
                            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                <Mail class="h-4 w-4" />
                                <span>{{ user.email }}</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-muted-foreground mt-1">
                                <Calendar class="h-4 w-4" />
                                <span>Joined {{ new Date(user.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-2">
                        <Link
                            :href="route('users.edit', user.id)"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            Edit
                        </Link>
                        <Link
                            :href="route('users.show', user.id)"
                            class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-700"
                        >
                            View
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="users.length === 0" class="flex flex-col items-center justify-center py-12">
                <Users class="h-12 w-12 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold">No users found</h3>
                <p class="mt-2 text-muted-foreground">Get started by creating your first user.</p>
                <Link
                    :href="route('users.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Add User
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
