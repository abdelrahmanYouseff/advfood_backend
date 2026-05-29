<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ChefHat } from 'lucide-vue-next';

const page = usePage();

const isKitchenPage = computed(() => page.url.startsWith('/kitchen'));

const tBoth = (ar: string, en: string) => `${ar} · ${en}`;
</script>

<template>
    <Link
        v-if="!isKitchenPage"
        :href="route('kitchen')"
        class="sticky top-0 z-50 flex w-full items-center justify-center gap-3 border-b-4 border-black bg-yellow-400 px-6 py-4 text-lg font-bold text-black shadow-md transition hover:bg-yellow-300 active:bg-yellow-500 md:text-xl"
    >
        <ChefHat class="h-8 w-8 shrink-0" aria-hidden="true" />
        <span>{{ tBoth('فتح المطبخ', 'Open Kitchen') }}</span>
    </Link>
</template>
