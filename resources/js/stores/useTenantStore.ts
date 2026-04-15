import { router, usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import type { Tenant } from '@/types';

export const useTenantStore = defineStore('tenant', () => {
    const page = usePage();

    const tenants = computed<Tenant[]>(
        () => (page.props.auth as any)?.tenants ?? [],
    );
    const activeTenantSlug = ref<string | null>(
        (page.props.auth as any)?.activeTenant ?? null,
    );

    // Keep activeTenantSlug in sync with Inertia page prop changes
    // (handles partial reloads, back/forward navigation, and auto-selection on login)
    watch(
        () =>
            (page.props.auth as any)?.activeTenant as string | null | undefined,
        (newVal) => {
            if (newVal !== undefined) {
                activeTenantSlug.value = newVal ?? null;
            }
        },
    );

    const activeTenant = computed<Tenant | null>(
        () =>
            tenants.value.find((t) => t.slug === activeTenantSlug.value) ??
            null,
    );

    function switchTenant(slug: string): void {
        router.post(
            '/api/switch-tenant',
            { slug },
            {
                preserveState: false,
                onSuccess: () => {
                    activeTenantSlug.value = slug;
                },
            },
        );
    }

    return { tenants, activeTenant, activeTenantSlug, switchTenant };
});
