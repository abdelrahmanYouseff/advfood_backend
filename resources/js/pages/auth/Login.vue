<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <div class="mb-6 flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Welcome Back</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Sign in to your account to continue</p>
                </div>

                <!-- Login Form -->
                <div class="rounded-2xl bg-white/80 backdrop-blur-sm p-8 shadow-xl dark:bg-gray-800/80">
                    <Head title="Sign In" />

                    <div v-if="status" class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                v-model="form.email"
                                placeholder="Enter your email"
                                class="mt-2 h-12 border-gray-300 bg-white/50 backdrop-blur-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white"
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">Password</Label>
                                <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400" :tabindex="5">
                                    Forgot password?
                                </TextLink>
                            </div>
                            <Input
                                id="password"
                                type="password"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                v-model="form.password"
                                placeholder="Enter your password"
                                class="mt-2 h-12 border-gray-300 bg-white/50 backdrop-blur-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="flex items-center">
                            <Label for="remember" class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300">
                                <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                                <span>Remember me for 30 days</span>
                            </Label>
                        </div>

                        <Button 
                            type="submit" 
                            class="h-12 w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg" 
                            :tabindex="4" 
                            :disabled="form.processing"
                        >
                            <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                            {{ form.processing ? 'Signing in...' : 'Sign In' }}
                        </Button>
                    </form>

                    <!-- Footer -->
                    <div class="mt-8 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Protected by enterprise-grade security
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
