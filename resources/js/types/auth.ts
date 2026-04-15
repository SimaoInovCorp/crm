export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Tenant = {
    id: number;
    name: string;
    slug: string;
    settings: Record<string, unknown> | null;
    role: 'owner' | 'admin' | 'member';
    created_at: string;
};

export type Auth = {
    user: User;
    tenants: Tenant[];
    activeTenant: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

export type EntityStatus = 'prospect' | 'active' | 'inactive' | 'customer';

export type Entity = {
    id: number;
    name: string;
    vat: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    status: EntityStatus;
    people_count?: number;
    deals_count?: number;
    created_at: string;
    updated_at: string;
};

export type Person = {
    id: number;
    entity_id: number | null;
    entity?: Pick<Entity, 'id' | 'name'> | null;
    name: string;
    email: string | null;
    phone: string | null;
    position: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
};

export type PaginatedResponse<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};
