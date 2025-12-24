<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Truck } from 'lucide-vue-next';

interface Props {
    defaultShippingProvider: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Shipping Settings',
        href: '/settings/shipping',
    },
];

const form = useForm({
    default_shipping_provider: props.defaultShippingProvider,
});

const submit = () => {
    form.patch(route('shipping-settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Shipping Settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall 
                    title="Shipping Settings" 
                    description="Choose the default shipping provider for new orders" 
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-4">
                        <Label>Default Shipping Provider</Label>
                        <div class="space-y-3">
                            <div 
                                @click="form.default_shipping_provider = 'leajlak'"
                                :class="[
                                    'flex items-center space-x-3 rounded-lg border p-4 cursor-pointer transition-colors',
                                    form.default_shipping_provider === 'leajlak' 
                                        ? 'border-primary bg-primary/5' 
                                        : 'hover:bg-accent'
                                ]"
                            >
                                <div class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                     :class="form.default_shipping_provider === 'leajlak' ? 'border-primary' : 'border-muted-foreground'">
                                    <div v-if="form.default_shipping_provider === 'leajlak'" class="h-2 w-2 rounded-full bg-primary"></div>
                                </div>
                                <Label class="flex-1 cursor-pointer font-normal">
                                    <div class="flex items-center gap-2">
                                        <Truck class="h-4 w-4" />
                                        <div>
                                            <div class="font-medium">Leajlak</div>
                                            <div class="text-sm text-muted-foreground">Current shipping provider</div>
                                        </div>
                                    </div>
                                </Label>
                            </div>
                            <div 
                                @click="form.default_shipping_provider = 'shadda'"
                                :class="[
                                    'flex items-center space-x-3 rounded-lg border p-4 cursor-pointer transition-colors',
                                    form.default_shipping_provider === 'shadda' 
                                        ? 'border-primary bg-primary/5' 
                                        : 'hover:bg-accent'
                                ]"
                            >
                                <div class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                     :class="form.default_shipping_provider === 'shadda' ? 'border-primary' : 'border-muted-foreground'">
                                    <div v-if="form.default_shipping_provider === 'shadda'" class="h-2 w-2 rounded-full bg-primary"></div>
                                </div>
                                <Label class="flex-1 cursor-pointer font-normal">
                                    <div class="flex items-center gap-2">
                                        <Truck class="h-4 w-4" />
                                        <div>
                                            <div class="font-medium">Shadda</div>
                                            <div class="text-sm text-muted-foreground">New shipping provider (testing)</div>
                                        </div>
                                    </div>
                                </Label>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.default_shipping_provider" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

