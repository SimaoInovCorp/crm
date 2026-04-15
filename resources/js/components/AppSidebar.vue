<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Bot,
    Building2,
    CalendarDays,
    ClipboardList,
    Info,
    LayoutGrid,
    Mail,
    MessageSquare,
    Package,
    Settings2,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TenantSwitcher from '@/components/TenantSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Entities',
        href: '/entities',
        icon: Building2,
    },
    {
        title: 'People',
        href: '/people',
        icon: Users,
    },
    {
        title: 'Deals',
        href: '/deals',
        icon: TrendingUp,
    },
    {
        title: 'Calendar',
        href: '/calendar',
        icon: CalendarDays,
    },
    {
        title: 'Email Templates',
        href: '/automation/email-templates',
        icon: Mail,
    },
    {
        title: 'Automation Rules',
        href: '/automation/rules',
        icon: Settings2,
    },
    {
        title: 'Lead Forms',
        href: '/lead-forms',
        icon: ClipboardList,
    },
    {
        title: 'Products',
        href: '/products',
        icon: Package,
    },
    {
        title: 'Product Statistics',
        href: '/products/statistics',
        icon: Package,
    },
    {
        title: 'Smart Chat',
        href: '/chat',
        icon: MessageSquare,
    },
    {
        title: 'AI Suggestions',
        href: '/ai/suggestions',
        icon: Bot,
    },
    {
        title: 'About',
        href: '/about',
        icon: Info,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <TenantSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
