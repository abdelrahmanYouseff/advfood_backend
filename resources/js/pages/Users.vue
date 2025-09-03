<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Users, Mail, Calendar, Trash2 } from 'lucide-vue-next';

interface Props {
    users: Array<{
        id: number;
        name: string;
        email: string;
        phone_number?: string;
        address?: string;
        country?: string;
        role: string;
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

const deleteUser = (userId: number, userName: string) => {
    if (confirm(`Are you sure you want to delete ${userName}? This action cannot be undone.`)) {
        router.delete(route('users.destroy', userId), {
            onSuccess: () => {
                // Success message will be handled by the backend
            },
            onError: () => {
                alert('Failed to delete user. Please try again.');
            }
        });
    }
};
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
                            <div class="flex items-center space-x-2">
                                <h3 class="font-semibold">{{ user.name }}</h3>
                                <span v-if="user.role === 'admin'" class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900/20 dark:text-red-400">
                                    Admin
                                </span>
                                <span v-else class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                    User
                                </span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                                <Mail class="h-4 w-4" />
                                <span>{{ user.email }}</span>
                            </div>
                            <div v-if="user.phone_number" class="flex items-center space-x-2 text-sm text-muted-foreground mt-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <span>{{ user.phone_number }}</span>
                            </div>
                            <div v-if="user.country" class="flex items-center space-x-2 text-sm text-muted-foreground mt-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>{{ user.country }}</span>
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
                        <button
                            @click="deleteUser(user.id, user.name)"
                            class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            title="Delete User"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
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
