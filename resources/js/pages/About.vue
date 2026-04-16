<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: { breadcrumbs: [{ title: 'About', href: '/about' }] },
});

const techStack = [
    {
        name: 'Laravel 12',
        description: 'PHP web application framework (backend)',
    },
    { name: 'PHP 8.3', description: 'Server-side scripting language' },
    {
        name: 'Vue 3',
        description: 'Progressive JavaScript UI framework (frontend)',
    },
    {
        name: 'Inertia.js',
        description: 'SPA adapter connecting Laravel and Vue',
    },
    { name: 'TypeScript', description: 'Typed superset of JavaScript' },
    { name: 'Tailwind CSS v4', description: 'Utility-first CSS framework' },
    { name: 'shadcn-vue', description: 'Accessible UI component library' },
    { name: 'Vite', description: 'Frontend build tool' },
    {
        name: 'OpenAI GPT',
        description: 'AI-powered Smart Chat and Suggestions',
    },
    { name: 'FullCalendar', description: 'Interactive calendar component' },
    { name: 'Laravel Fortify', description: 'Authentication (session-based)' },
    { name: 'MySQL', description: 'Relational database' },
];
</script>

<template>
    <Head title="About" />

    <div
        class="crm-table-container max-w-2xl space-y-8 p-6"
        style="background-color: #ece4d9"
    >
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold">About InovCorp CRM</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                A modern, multi-tenant CRM built for growing businesses.
            </p>
        </div>

        <!-- App Info -->
        <div
            class="crm-table-container card-depth space-y-3 rounded-lg border p-5"
        >
            <h2 class="text-lg font-semibold">Application Info</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs text-muted-foreground">Author</p>
                    <p class="font-medium">Simão Morais</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Version</p>
                    <p class="font-medium">1.0.0</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Release Date</p>
                    <p class="font-medium">April 2026</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">License</p>
                    <p class="font-medium">MIT</p>
                </div>
            </div>
        </div>

        <!-- Tech Stack -->
        <div
            class="crm-table-container card-depth space-y-3 rounded-lg border p-5"
        >
            <h2 class="text-lg font-semibold">Tech Stack</h2>
            <div class="space-y-0">
                <div
                    v-for="(tech, idx) in techStack"
                    :key="tech.name"
                    class="flex items-start justify-between border-t py-2 text-sm first:border-t-0"
                    :class="idx % 2 === 0 ? 'bg-background' : 'bg-muted/20'"
                >
                    <span class="font-medium">{{ tech.name }}</span>
                    <span
                        class="max-w-[55%] text-right text-muted-foreground"
                        >{{ tech.description }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Credits -->
        <div class="crm-table-container rounded-lg border p-4 text-sm">
            <p class="mb-1 font-semibold text-blue-800 dark:text-blue-300">
                💡 About this project
            </p>
            <p class="text-blue-700 dark:text-blue-400">
                InovCorp CRM is a full-featured, multi-tenant CRM system with
                deal pipeline management, contact tracking, calendar, automation
                rules, AI-powered insights, lead forms, and more — all built
                with modern web technologies following SOLID principles.
            </p>
        </div>
    </div>
</template>
