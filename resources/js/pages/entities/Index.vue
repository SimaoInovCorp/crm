<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, reactive, onMounted } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFormErrors } from '@/composables/useFormErrors';
import type { Entity, EntityStatus, PaginatedResponse } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Entities', href: '/entities' }],
    },
});

const STATUS_LABELS: Record<EntityStatus, string> = {
    prospect: 'Prospect',
    active: 'Active',
    inactive: 'Inactive',
    customer: 'Customer',
};

const STATUS_VARIANTS: Record<
    EntityStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    prospect: 'secondary',
    active: 'default',
    inactive: 'outline',
    customer: 'default',
};

// ─── Data ────────────────────────────────────────────────────────────────────
const entities = ref<Entity[]>([]);
const pagination = ref<Omit<PaginatedResponse<Entity>, 'data'> | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const search = ref('');
const statusFilter = ref<EntityStatus | ''>('');

// ─── Modals ───────────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingEntity = ref<Entity | null>(null);
const deletingEntity = ref<Entity | null>(null);
const saving = ref(false);
const { formErrors, extractErrors, clearErrors } = useFormErrors();

const form = reactive({
    name: '',
    vat: '',
    email: '',
    phone: '',
    address: '',
    status: 'prospect' as EntityStatus,
});

function resetForm() {
    form.name = '';
    form.vat = '';
    form.email = '';
    form.phone = '';
    form.address = '';
    form.status = 'prospect';
    clearErrors();
}

// ─── Fetch ────────────────────────────────────────────────────────────────────
async function fetchEntities(page = 1) {
    loading.value = true;
    error.value = null;

    try {
        const params: Record<string, string | number> = { page };

        if (search.value) {
            params.search = search.value;
        }

        if (statusFilter.value) {
            params.status = statusFilter.value;
        }

        const { data } = await axios.get<PaginatedResponse<Entity>>(
            '/api/entities',
            { params },
        );
        entities.value = data.data;
        const { data: _d, ...meta } = data;
        pagination.value = meta;
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to load entities.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetchEntities());

function exportCsv() {
    const params = new URLSearchParams();

    if (search.value) {
params.set('search', search.value);
}

    if (statusFilter.value) {
params.set('status', statusFilter.value);
}

    window.location.href = `/api/entities/export?${params.toString()}`;
}

let searchTimeout: ReturnType<typeof setTimeout>;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchEntities(), 350);
}

// ─── Create ───────────────────────────────────────────────────────────────────
function openCreate() {
    resetForm();
    showCreateModal.value = true;
}

async function submitCreate() {
    saving.value = true;
    clearErrors();

    try {
        await axios.post('/api/entities', form);
        showCreateModal.value = false;
        fetchEntities();
    } catch (e: unknown) {
        if (!extractErrors(e)) {
            error.value =
                (e as any)?.response?.data?.message ??
                'Failed to create entity.';
        }
    } finally {
        saving.value = false;
    }
}

// ─── Edit ─────────────────────────────────────────────────────────────────────
function openEdit(entity: Entity) {
    editingEntity.value = entity;
    form.name = entity.name;
    form.vat = entity.vat ?? '';
    form.email = entity.email ?? '';
    form.phone = entity.phone ?? '';
    form.address = entity.address ?? '';
    form.status = entity.status;
    clearErrors();
    showEditModal.value = true;
}

async function submitEdit() {
    if (!editingEntity.value) {
        return;
    }

    saving.value = true;
    clearErrors();

    try {
        await axios.put(`/api/entities/${editingEntity.value.id}`, form);
        showEditModal.value = false;
        fetchEntities();
    } catch (e: unknown) {
        if (!extractErrors(e)) {
            error.value =
                (e as any)?.response?.data?.message ??
                'Failed to update entity.';
        }
    } finally {
        saving.value = false;
    }
}

// ─── Delete ───────────────────────────────────────────────────────────────────
function openDelete(entity: Entity) {
    deletingEntity.value = entity;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    if (!deletingEntity.value) {
        return;
    }

    saving.value = true;

    try {
        await axios.delete(`/api/entities/${deletingEntity.value.id}`);
        showDeleteModal.value = false;
        fetchEntities();
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to delete entity.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Head title="Entities" />

    <div
        class="crm-table-container flex flex-col gap-4 p-4"
        style="background-color: #ece4d9"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Entities</h1>
            <div class="flex gap-2">
                <Button variant="outline" @click="exportCsv">Export CSV</Button>
                <Button @click="openCreate">+ New Entity</Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="crm-table-container flex gap-2">
            <Input
                v-model="search"
                placeholder="Search by name, VAT or email…"
                class="max-w-xs"
                @input="onSearchInput"
            />
            <Select v-model="statusFilter" @update:modelValue="fetchEntities()">
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All statuses</SelectItem>
                    <SelectItem value="prospect">Prospect</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                    <SelectItem value="customer">Customer</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

        <!-- Table -->
        <div class="crm-table-container">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">VAT</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Phone</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">People</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            <span
                                class="crm-spinner mx-auto block opacity-60"
                            />
                        </td>
                    </tr>
                    <tr v-else-if="entities.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No entities found.
                        </td>
                    </tr>
                    <tr
                        v-for="entity in entities"
                        :key="entity.id"
                        class="border-b transition-colors last:border-0 odd:bg-rose-50 even:bg-blue-50 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3">
                            <a
                                :href="`/entities/${entity.id}`"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ entity.name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ entity.vat ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ entity.email ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ entity.phone ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge :variant="STATUS_VARIANTS[entity.status]">
                                {{ STATUS_LABELS[entity.status] }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ entity.people_count ?? 0 }}
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openEdit(entity)"
                                >Edit</Button
                            >
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="openDelete(entity)"
                                >Delete</Button
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            v-if="pagination && pagination.last_page > 1"
            class="flex items-center gap-2"
        >
            <Button
                v-for="p in pagination.last_page"
                :key="p"
                :variant="p === pagination.current_page ? 'default' : 'outline'"
                size="sm"
                @click="fetchEntities(p)"
            >
                {{ p }}
            </Button>
        </div>
    </div>

    <!-- Create Modal -->
    <Dialog v-model:open="showCreateModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>New Entity</DialogTitle>
            </DialogHeader>
            <div class="grid gap-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="create-name">Name *</Label>
                    <Input id="create-name" v-model="form.name" />
                    <p v-if="formErrors.name" class="text-xs text-destructive">
                        {{ formErrors.name }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="create-vat">VAT</Label>
                    <Input id="create-vat" v-model="form.vat" />
                    <p v-if="formErrors.vat" class="text-xs text-destructive">
                        {{ formErrors.vat }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="create-email">Email</Label>
                    <Input
                        id="create-email"
                        v-model="form.email"
                        type="email"
                    />
                    <p v-if="formErrors.email" class="text-xs text-destructive">
                        {{ formErrors.email }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="create-phone">Phone</Label>
                    <Input id="create-phone" v-model="form.phone" />
                    <p v-if="formErrors.phone" class="text-xs text-destructive">
                        {{ formErrors.phone }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="create-address">Address</Label>
                    <Input id="create-address" v-model="form.address" />
                    <p
                        v-if="formErrors.address"
                        class="text-xs text-destructive"
                    >
                        {{ formErrors.address }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="create-status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="create-status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="prospect">Prospect</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="customer">Customer</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showCreateModal = false"
                    >Cancel</Button
                >
                <Button :disabled="saving" @click="submitCreate">
                    {{ saving ? 'Saving…' : 'Create' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog v-model:open="showEditModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Entity</DialogTitle>
            </DialogHeader>
            <div class="grid gap-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="edit-name">Name *</Label>
                    <Input id="edit-name" v-model="form.name" />
                    <p v-if="formErrors.name" class="text-xs text-destructive">
                        {{ formErrors.name }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-vat">VAT</Label>
                    <Input id="edit-vat" v-model="form.vat" />
                    <p v-if="formErrors.vat" class="text-xs text-destructive">
                        {{ formErrors.vat }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-email">Email</Label>
                    <Input id="edit-email" v-model="form.email" type="email" />
                    <p v-if="formErrors.email" class="text-xs text-destructive">
                        {{ formErrors.email }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-phone">Phone</Label>
                    <Input id="edit-phone" v-model="form.phone" />
                    <p v-if="formErrors.phone" class="text-xs text-destructive">
                        {{ formErrors.phone }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-address">Address</Label>
                    <Input id="edit-address" v-model="form.address" />
                    <p
                        v-if="formErrors.address"
                        class="text-xs text-destructive"
                    >
                        {{ formErrors.address }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="edit-status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="prospect">Prospect</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="customer">Customer</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showEditModal = false"
                    >Cancel</Button
                >
                <Button :disabled="saving" @click="submitEdit">
                    {{ saving ? 'Saving…' : 'Update' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:open="showDeleteModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Entity</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <strong>{{ deletingEntity?.name }}</strong
                >? This will also remove all linked people. This action cannot
                be undone.
            </p>
            <DialogFooter>
                <Button variant="outline" @click="showDeleteModal = false"
                    >Cancel</Button
                >
                <Button
                    variant="destructive"
                    :disabled="saving"
                    @click="confirmDelete"
                >
                    {{ saving ? 'Deleting…' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
