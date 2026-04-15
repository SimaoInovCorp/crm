import { createInertiaApp } from '@inertiajs/vue3';
import axios from 'axios';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia();

// Configure axios defaults so it works with Laravel's web middleware group:
// - withCredentials ensures cookies (session + XSRF) are sent on same-origin requests
// - withXSRFToken tells axios to read the XSRF-TOKEN cookie and attach it as
//   X-XSRF-TOKEN header for mutating requests (POST, PUT, PATCH, DELETE),
//   satisfying Laravel's CSRF verification (PreventRequestForgery middleware).
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Attach active tenant slug to every Axios request so TenantMiddleware can resolve it
axios.interceptors.request.use((config) => {
    const slug = (pinia.state.value as any)?.tenant?.activeTenantSlug as
        | string
        | null
        | undefined;

    if (slug) {
        config.headers['X-Tenant'] = slug;
    }

    return config;
});

createInertiaApp({
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin).use(pinia).mount(el);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        const page = pages[`./pages/${name}.vue`] as any;

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        // Pages use defineOptions({ layout: { breadcrumbs/title/description } }) — a plain
        // config object, NOT a component. We read that config and replace it with a real
        // render-function layout so Inertia wraps the page in the right shell with props.
        const layoutDef = page.default.layout;
        const isConfig =
            layoutDef !== null &&
            layoutDef !== undefined &&
            typeof layoutDef === 'object' &&
            !Array.isArray(layoutDef) &&
            typeof layoutDef !== 'function';

        if (name === 'Welcome') {
            page.default.layout = null;
        } else if (name.startsWith('auth/')) {
            // Auth pages pass title + description to AuthSimpleLayout
            const title: string = isConfig ? (layoutDef.title ?? '') : '';
            const description: string = isConfig
                ? (layoutDef.description ?? '')
                : '';
            page.default.layout = (_h: any, child: any) =>
                h(AuthLayout, { title, description }, () => child);
        } else if (name.startsWith('settings/')) {
            // Settings: AppLayout (sidebar + navbar) wrapping the settings sidebar
            const breadcrumbs: BreadcrumbItem[] = isConfig
                ? (layoutDef.breadcrumbs ?? [])
                : [];
            page.default.layout = (_h: any, child: any) =>
                h(AppLayout, { breadcrumbs }, () =>
                    h(SettingsLayout, {}, () => child),
                );
        } else {
            // All other authenticated pages: AppLayout with breadcrumbs in the header
            const breadcrumbs: BreadcrumbItem[] = isConfig
                ? (layoutDef.breadcrumbs ?? [])
                : [];
            page.default.layout = (_h: any, child: any) =>
                h(AppLayout, { breadcrumbs }, () => child);
        }

        return page;
    },
    progress: {
        color: '#dc2626',
        delay: 100,
    },
});

// This will set light / dark mode on page load. It also sets up a watcher so that if the user changes their preference, the UI will update immediately.
initializeTheme();
