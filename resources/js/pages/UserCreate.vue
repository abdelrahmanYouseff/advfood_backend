<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, Mail, Lock, Phone, MapPin, Globe } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Add User',
        href: '/users/create',
    },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
    phone_number: '',
    address: '',
    country: '',
});

const submit = () => {
    form.post(route('users.store'), {
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.log('Form errors:', errors);
        },
    });
};
</script>

<template>
    <Head title="Add User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col">
            <!-- Header Section -->
            <div class="border-b bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('users.index')"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Users
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add New User</h1>
                            <p class="text-gray-600 dark:text-gray-400">Create a new user account</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            type="submit"
                            form="user-form"
                            :disabled="form.processing"
                            class="inline-flex items-center"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creating...' : 'Create User' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Form Section -->
            <div class="px-6 py-8">
                <div class="mx-auto max-w-4xl">
                    <div class="rounded-2xl bg-white p-8 shadow-xl dark:bg-gray-800">
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">User Information</h2>
                            <p class="text-gray-600 dark:text-gray-400">Enter the details for the new user account</p>
                        </div>

                        <form @submit.prevent="submit" id="user-form" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Name -->
                                <div class="space-y-2">
                                    <Label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <User class="mr-1 inline h-4 w-4" />
                                        Full Name <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Enter full name"
                                        :class="{ 'border-red-500': form.errors.name }"
                                        required
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div class="space-y-2">
                                    <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Mail class="mr-1 inline h-4 w-4" />
                                        Email Address <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="Enter email address"
                                        :class="{ 'border-red-500': form.errors.email }"
                                        required
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <!-- Password -->
                                <div class="space-y-2">
                                    <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Lock class="mr-1 inline h-4 w-4" />
                                        Password <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Enter password"
                                        :class="{ 'border-red-500': form.errors.password }"
                                        required
                                    />
                                    <InputError :message="form.errors.password" />
                                </div>

                                <!-- Confirm Password -->
                                <div class="space-y-2">
                                    <Label for="password_confirmation" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Lock class="mr-1 inline h-4 w-4" />
                                        Confirm Password <span class="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Confirm password"
                                        :class="{ 'border-red-500': form.errors.password_confirmation }"
                                        required
                                    />
                                    <InputError :message="form.errors.password_confirmation" />
                                </div>

                                <!-- Role -->
                                <div class="space-y-2">
                                    <Label for="role" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Role <span class="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="role"
                                        v-model="form.role"
                                        :class="[
                                            'w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                            { 'border-red-500': form.errors.role }
                                        ]"
                                        required
                                    >
                                        <option value="">Select role</option>
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <InputError :message="form.errors.role" />
                                </div>

                                <!-- Phone Number -->
                                <div class="space-y-2">
                                    <Label for="phone_number" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Phone class="mr-1 inline h-4 w-4" />
                                        Phone Number
                                    </Label>
                                    <Input
                                        id="phone_number"
                                        v-model="form.phone_number"
                                        type="tel"
                                        placeholder="Enter phone number"
                                        :class="{ 'border-red-500': form.errors.phone_number }"
                                    />
                                    <InputError :message="form.errors.phone_number" />
                                </div>

                                <!-- Country -->
                                <div class="space-y-2">
                                    <Label for="country" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <Globe class="mr-1 inline h-4 w-4" />
                                        Country
                                    </Label>
                                    <Input
                                        id="country"
                                        v-model="form.country"
                                        type="text"
                                        placeholder="Enter country"
                                        :class="{ 'border-red-500': form.errors.country }"
                                    />
                                    <InputError :message="form.errors.country" />
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="space-y-2">
                                <Label for="address" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <MapPin class="mr-1 inline h-4 w-4" />
                                    Address
                                </Label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Enter full address"
                                    :class="[
                                        'w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white',
                                        { 'border-red-500': form.errors.address }
                                    ]"
                                    rows="3"
                                ></textarea>
                                <InputError :message="form.errors.address" />
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end space-x-4 border-t pt-6">
                                <Link
                                    :href="route('users.index')"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    Cancel
                                </Link>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center"
                                >
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Creating...' : 'Create User' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
