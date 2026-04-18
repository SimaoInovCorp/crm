<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, reactive, onMounted } from 'vue';
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
import type { Entity, Person, PaginatedResponse } from '@/types';
import { useFormErrors } from '@/composables/useFormErrors';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'People', href: '/people' }],
    },
});

// ─── Data ────────────────────────────────────────────────────────────────────
const people = ref<Person[]>([]);
const entities = ref<Entity[]>([]);
const pagination = ref<Omit<PaginatedResponse<Person>, 'data'> | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const search = ref('');
const entityFilter = ref<string>('');

// Pre-fill entity_id from query param (coming from Entity Show page)
const urlParams = new URLSearchParams(window.location.search);

if (urlParams.get('entity_id')) {
    entityFilter.value = urlParams.get('entity_id')!;
}

// ─── Modals ───────────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingPerson = ref<Person | null>(null);
const deletingPerson = ref<Person | null>(null);
const saving = ref(false);
const { formErrors, extractErrors, clearErrors } = useFormErrors();

const form = reactive({
    name: '',
    entity_id: '' as string | number,
    email: '',
    phone: '',
    position: '',
    notes: '',
});

function resetForm() {
    form.name = '';
    form.entity_id = '';
    form.email = '';
    form.phone = '';
    form.position = '';
    form.notes = '';
    clearErrors();
}

// ─── Fetch ────────────────────────────────────────────────────────────────────
async function fetchPeople(page = 1) {
    loading.value = true;
    error.value = null;

    try {
        const params: Record<string, string | number> = { page };

        if (search.value) {
            params.search = search.value;
        }

        if (entityFilter.value) {
            params.entity_id = entityFilter.value;
        }

        const { data } = await axios.get<PaginatedResponse<Person>>(
            '/api/people',
            { params },
        );
        people.value = data.data;
        const { data: _d, ...meta } = data;
        pagination.value = meta;
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to load people.';
    } finally {
        loading.value = false;
    }
}

async function fetchEntities() {
    try {
        const { data } = await axios.get<PaginatedResponse<Entity>>(
            '/api/entities',
            { params: { per_page: 100 } },
        );
        entities.value = data.data;
    } catch {
        // non-critical
    }
}

onMounted(() => {
    fetchPeople();
    fetchEntities();
});

function exportCsv() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (entityFilter.value) params.set('entity_id', entityFilter.value);
    window.location.href = `/api/people/export?${params.toString()}`;
}

let searchTimeout: ReturnType<typeof setTimeout>;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchPeople(), 350);
}

// ─── Create ───────────────────────────────────────────────────────────────────
function openCreate() {
    resetForm();

    if (entityFilter.value) {
        form.entity_id = entityFilter.value;
    }

    showCreateModal.value = true;
}

async function submitCreate() {
    saving.value = true;
    clearErrors();

    try {
        const payload = { ...form, entity_id: form.entity_id || null };
        await axios.post('/api/people', payload);
        showCreateModal.value = false;
        fetchPeople();
    } catch (e: unknown) {
        if (!extractErrors(e)) {
            error.value =
                (e as any)?.response?.data?.message ??
                'Failed to create person.';
        }
    } finally {
        saving.value = false;
    }
}

// ─── Edit ─────────────────────────────────────────────────────────────────────
function openEdit(person: Person) {
    editingPerson.value = person;
    form.name = person.name;
    form.entity_id = person.entity_id ?? '';
    form.email = person.email ?? '';
    form.phone = person.phone ?? '';
    form.position = person.position ?? '';
    form.notes = person.notes ?? '';
    clearErrors();
    showEditModal.value = true;
}

async function submitEdit() {
    if (!editingPerson.value) {
        return;
    }

    saving.value = true;
    clearErrors();

    try {
        const payload = { ...form, entity_id: form.entity_id || null };
        await axios.put(`/api/people/${editingPerson.value.id}`, payload);
        showEditModal.value = false;
        fetchPeople();
    } catch (e: unknown) {
        if (!extractErrors(e)) {
            error.value =
                (e as any)?.response?.data?.message ??
                'Failed to update person.';
        }
    } finally {
        saving.value = false;
    }
}

// ─── Delete ───────────────────────────────────────────────────────────────────
function openDelete(person: Person) {
    deletingPerson.value = person;
    showDeleteModal.value = true;
}

async function confirmDelete() {
    if (!deletingPerson.value) {
        return;
    }

    saving.value = true;

    try {
        await axios.delete(`/api/people/${deletingPerson.value.id}`);
        showDeleteModal.value = false;
        fetchPeople();
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to delete person.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Head title="People" />

    <div
        class="crm-table-container flex flex-col gap-4 p-4"
        style="background-color: #ece4d9"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">People</h1>
            <div class="flex gap-2">
                <Button variant="outline" @click="exportCsv">Export CSV</Button>
                <Button @click="openCreate">+ New Person</Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="crm-table-container flex gap-2">
            <Input
                v-model="search"
                placeholder="Search by name, email or position…"
                class="max-w-xs"
                @input="onSearchInput"
            />
            <Select v-model="entityFilter" @update:modelValue="fetchPeople()">
                <SelectTrigger class="w-56">
                    <SelectValue placeholder="All entities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All entities</SelectItem>
                    <SelectItem
                        v-for="e in entities"
                        :key="e.id"
                        :value="String(e.id)"
                    >
                        {{ e.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

        <!-- Table -->
        <div class="crm-table-container overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Entity</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Phone</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Position
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            <span
                                class="crm-spinner mx-auto block opacity-60"
                            />
                        </td>
                    </tr>
                    <tr v-else-if="people.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No people found.
                        </td>
                    </tr>
                    <tr
                        v-for="person in people"
                        :key="person.id"
                        class="border-b transition-colors last:border-0 odd:bg-rose-50 even:bg-blue-50 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3">
                            <a
                                :href="`/people/${person.id}`"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ person.name }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <a
                                v-if="person.entity"
                                :href="`/entities/${person.entity_id}`"
                                class="text-muted-foreground hover:underline"
                            >
                                {{ person.entity.name }}
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ person.email ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ person.phone ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ person.position ?? '—' }}
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openEdit(person)"
                                >Edit</Button
                            >
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="openDelete(person)"
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
                @click="fetchPeople(p)"
            >
                {{ p }}
            </Button>
        </div>
    </div>

    <!-- Create Modal -->
    <Dialog v-model:open="showCreateModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>New Person</DialogTitle>
            </DialogHeader>
            <div class="grid gap-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="pc-name">Name *</Label>
                    <Input id="pc-name" v-model="form.name" />
                    <p v-if="formErrors.name" class="text-xs text-destructive">
                        {{ formErrors.name }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pc-entity">Entity</Label>
                    <Select v-model="form.entity_id">
                        <SelectTrigger id="pc-entity">
                            <SelectValue placeholder="No entity" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">No entity</SelectItem>
                            <SelectItem
                                v-for="e in entities"
                                :key="e.id"
                                :value="String(e.id)"
                            >
                                {{ e.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="formErrors.entity_id"
                        class="text-xs text-destructive"
                    >
                        {{ formErrors.entity_id }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pc-email">Email</Label>
                    <Input id="pc-email" v-model="form.email" type="email" />
                    <p v-if="formErrors.email" class="text-xs text-destructive">
                        {{ formErrors.email }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pc-phone">Phone</Label>
                    <Input id="pc-phone" v-model="form.phone" />
                    <p v-if="formErrors.phone" class="text-xs text-destructive">
                        {{ formErrors.phone }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pc-position">Position</Label>
                    <Input id="pc-position" v-model="form.position" />
                    <p
                        v-if="formErrors.position"
                        class="text-xs text-destructive"
                    >
                        {{ formErrors.position }}
                    </p>
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
                <DialogTitle>Edit Person</DialogTitle>
            </DialogHeader>
            <div class="grid gap-4 py-2">
                <div class="grid gap-1.5">
                    <Label for="pe-name">Name *</Label>
                    <Input id="pe-name" v-model="form.name" />
                    <p v-if="formErrors.name" class="text-xs text-destructive">
                        {{ formErrors.name }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pe-entity">Entity</Label>
                    <Select v-model="form.entity_id">
                        <SelectTrigger id="pe-entity">
                            <SelectValue placeholder="No entity" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">No entity</SelectItem>
                            <SelectItem
                                v-for="e in entities"
                                :key="e.id"
                                :value="String(e.id)"
                            >
                                {{ e.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pe-email">Email</Label>
                    <Input id="pe-email" v-model="form.email" type="email" />
                    <p v-if="formErrors.email" class="text-xs text-destructive">
                        {{ formErrors.email }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pe-phone">Phone</Label>
                    <Input id="pe-phone" v-model="form.phone" />
                    <p v-if="formErrors.phone" class="text-xs text-destructive">
                        {{ formErrors.phone }}
                    </p>
                </div>
                <div class="grid gap-1.5">
                    <Label for="pe-position">Position</Label>
                    <Input id="pe-position" v-model="form.position" />
                    <p
                        v-if="formErrors.position"
                        class="text-xs text-destructive"
                    >
                        {{ formErrors.position }}
                    </p>
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
                <DialogTitle>Delete Person</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <strong>{{ deletingPerson?.name }}</strong
                >? This action cannot be undone.
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
