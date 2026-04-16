<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex flex-1 flex-col items-center justify-center gap-6 rounded-lg bg-white p-10 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <div class="flex flex-col items-center gap-2 text-center">
                        <h1
                            class="text-4xl font-black tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]"
                        >
                            ino<span class="text-red-500">V</span>corp
                        </h1>
                        <p class="text-lg text-[#706f6c] dark:text-[#A1A09A]">
                            Your all-in-one CRM platform
                        </p>
                    </div>

                    <div
                        class="grid w-full max-w-sm grid-cols-1 gap-4 sm:grid-cols-2"
                    >
                        <div
                            class="rounded-lg border border-[#e3e3e0] p-4 dark:border-[#3E3E3A]"
                        >
                            <h3
                                class="mb-1 font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Entities & Contacts
                            </h3>
                            <p
                                class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Manage companies and the people within them from
                                one place.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-[#e3e3e0] p-4 dark:border-[#3E3E3A]"
                        >
                            <h3
                                class="mb-1 font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Deals & Pipeline
                            </h3>
                            <p
                                class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Track opportunities from first contact to closed
                                deal.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-[#e3e3e0] p-4 dark:border-[#3E3E3A]"
                        >
                            <h3
                                class="mb-1 font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                Automation
                            </h3>
                            <p
                                class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Set up rules and follow-up emails to keep leads
                                engaged.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-[#e3e3e0] p-4 dark:border-[#3E3E3A]"
                        >
                            <h3
                                class="mb-1 font-semibold text-[#1b1b18] dark:text-[#EDEDEC]"
                            >
                                AI Insights
                            </h3>
                            <p
                                class="text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                Get smart suggestions and AI-powered deal
                                analysis.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                        >
                            Go to Dashboard
                        </Link>
                    </div>
                </div>
            </main>
        </div>

        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
