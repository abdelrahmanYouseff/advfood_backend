<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { MessageCircle } from 'lucide-vue-next';

interface Props {
    accountSid: string;
    hasAuthToken: boolean;
    whatsappFrom: string;
    templateSid: string;
    templateName: string;
}

const props = defineProps<Props>();
const page = usePage();

const flashSuccess = computed(() => (page.props.flash as { success?: string })?.success);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Twilio Settings',
        href: '/settings/twilio',
    },
];

const form = useForm({
    account_sid: props.accountSid,
    auth_token: '',
    whatsapp_from: props.whatsappFrom,
    template_sid: props.templateSid,
    template_name: props.templateName,
});

const testForm = useForm({
    phone: '',
});

const submit = () => {
    form.patch(route('twilio-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.auth_token = '';
        },
    });
};

const sendTest = () => {
    testForm.post(route('twilio-settings.test-message'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Twilio Settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <HeadingSmall
                    title="Twilio WhatsApp"
                    description="Configure Twilio credentials and the WhatsApp content template used for kitchen order alerts."
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="account_sid">Account SID</Label>
                        <Input
                            id="account_sid"
                            v-model="form.account_sid"
                            required
                            autocomplete="off"
                            placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        />
                        <InputError :message="form.errors.account_sid" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="auth_token">Primary Auth Token</Label>
                        <Input
                            id="auth_token"
                            v-model="form.auth_token"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="hasAuthToken ? 'Leave blank to keep the current token' : 'Enter your auth token'"
                        />
                        <p v-if="hasAuthToken" class="text-sm text-muted-foreground">
                            A token is already saved. Leave this field empty unless you want to replace it.
                        </p>
                        <InputError :message="form.errors.auth_token" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="whatsapp_from">WhatsApp From Number</Label>
                        <Input
                            id="whatsapp_from"
                            v-model="form.whatsapp_from"
                            required
                            placeholder="+14155238886 or whatsapp:+14155238886"
                        />
                        <p class="text-sm text-muted-foreground">
                            Your Twilio WhatsApp sender number (E.164 format).
                        </p>
                        <InputError :message="form.errors.whatsapp_from" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="template_name">Message Template Name</Label>
                        <Input
                            id="template_name"
                            v-model="form.template_name"
                            required
                            placeholder="new_kitchen_order_initiate"
                        />
                        <InputError :message="form.errors.template_name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="template_sid">Message Template Content SID</Label>
                        <Input
                            id="template_sid"
                            v-model="form.template_sid"
                            required
                            placeholder="HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                        />
                        <p class="text-sm text-muted-foreground">
                            Default: <code>new_kitchen_order_initiate</code> (HXf9b9978d7c4b3c44e8ef92275e33eff4)
                        </p>
                        <InputError :message="form.errors.template_sid" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                                Saved.
                            </p>
                            <p v-show="flashSuccess && !form.recentlySuccessful" class="text-sm text-neutral-600">
                                {{ flashSuccess }}
                            </p>
                        </Transition>
                    </div>
                </form>

                <div class="rounded-lg border p-4 space-y-4">
                    <div class="flex items-center gap-2">
                        <MessageCircle class="h-5 w-5 text-green-600" />
                        <h3 class="font-medium">Send Test Message</h3>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Sends the configured WhatsApp template to a phone number using your saved Twilio credentials.
                    </p>

                    <form @submit.prevent="sendTest" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="grid flex-1 gap-2">
                            <Label for="test_phone">Phone Number</Label>
                            <Input
                                id="test_phone"
                                v-model="testForm.phone"
                                required
                                placeholder="+966501234567"
                            />
                            <InputError :message="testForm.errors.phone || (page.props.errors as Record<string, string>)?.test_phone" />
                        </div>
                        <Button type="submit" variant="outline" :disabled="testForm.processing">
                            Send Test
                        </Button>
                    </form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
