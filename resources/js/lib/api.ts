import axios from 'axios';

/**
 * Module-level slot for the active tenant slug.
 * Updated by useTenantStore — read by the request interceptor.
 * This avoids circular import between api.ts ← store → api.ts.
 */
let _tenantSlug: string | null = null;

export function bindActiveTenantSlug(slug: string | null): void {
    _tenantSlug = slug;
}

const api = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

api.interceptors.request.use((config) => {
    if (_tenantSlug) {
        config.headers['X-Tenant'] = _tenantSlug;
    }

    return config;
});

export default api;
