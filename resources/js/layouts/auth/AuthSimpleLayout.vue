<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
// import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <!-- <AppLogoIcon
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            /> -->
                            <div
                                class="mb-2 flex flex-col items-center gap-2 text-center"
                            >
                                <h1
                                    class="text-4xl font-black tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                                >
                                    ino<span class="text-red-500">V</span>corp
                                </h1>
                                <p
                                    class="mb-2 text-lg text-[#706f6c] dark:text-[#A1A09A]"
                                >
                                    Your all-in-one CRM platform
                                </p>
                            </div>
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
