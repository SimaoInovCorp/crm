<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, onMounted } from 'vue';
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
import { Textarea } from '@/components/ui/textarea';
import { useFormErrors } from '@/composables/useFormErrors';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Products', href: '/products' }] },
});

interface Product {
    id: number;
    name: string;
    description: string | null;
    price: string | null;
}

const products = ref<Product[]>([]);
const loading = ref(true);
const search = ref('');

const filtered = computed(() =>
    search.value
        ? products.value.filter((p) =>
              p.name.toLowerCase().includes(search.value.toLowerCase()),
          )
        : products.value,
);

// ─── Create ────────────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const saving = ref(false);
const { formErrors, extractErrors, clearErrors } = useFormErrors();
const form = ref({ name: '', description: '', price: '' });

// ─── Edit ─────────────────────────────────────────────────────────────────────
const showEditModal = ref(false);
const editingProduct = ref<Product | null>(null);
const editForm = ref({ name: '', description: '', price: '' });

// ─── Delete ───────────────────────────────────────────────────────────────────
const showDeleteModal = ref(false);
const productToDelete = ref<Product | null>(null);
const deleting = ref(false);

async function fetchProducts() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/products', {
            params: { per_page: 200 },
        });
        products.value = data.data;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchProducts);

function openCreate() {
    form.value = { name: '', description: '', price: '' };
    clearErrors();
    showCreateModal.value = true;
}

async function submitCreate() {
    saving.value = true;
    clearErrors();
    try {
        await axios.post('/api/products', form.value);
        showCreateModal.value = false;
        await fetchProducts();
    } catch (err: unknown) {
        extractErrors(err);
    } finally {
        saving.value = false;
    }
}

function openEdit(product: Product) {
    editingProduct.value = product;
    editForm.value = {
        name: product.name,
        description: product.description ?? '',
        price: product.price ?? '',
    };
    clearErrors();
    showEditModal.value = true;
}

async function submitEdit() {
    if (!editingProduct.value) return;
    saving.value = true;
    clearErrors();
    try {
        await axios.put(
            `/api/products/${editingProduct.value.id}`,
            editForm.value,
        );
        showEditModal.value = false;
        await fetchProducts();
    } catch (err: unknown) {
        extractErrors(err);
    } finally {
        saving.value = false;
    }
}

function confirmDelete(product: Product) {
    productToDelete.value = product;
    showDeleteModal.value = true;
}

async function deleteProduct() {
    if (!productToDelete.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/api/products/${productToDelete.value.id}`);
        showDeleteModal.value = false;
        products.value = products.value.filter(
            (p) => p.id !== productToDelete.value!.id,
        );
    } finally {
        deleting.value = false;
        productToDelete.value = null;
    }
}

function fmt(price: string | null) {
    if (!price) return '—';
    return new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(price));
}
</script>

<template>
    <Head title="Products" />

    <div
        class="crm-table-container space-y-6 p-6"
        style="background-color: #ece4d9"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Products</h1>
            <div class="flex gap-2">
                <Button as="a" href="/products/statistics" variant="outline"
                    >📊 Statistics</Button
                >
                <Button @click="openCreate">+ New Product</Button>
            </div>
        </div>

        <!-- Search -->
        <div class="crm-table-container">
            <Input
                v-model="search"
                placeholder="Search products…"
                class="max-w-xs"
            />
        </div>

        <!-- Table -->
        <div class="crm-table-container">
            <table class="w-full text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Description
                        </th>
                        <th class="px-4 py-3 text-right font-medium">Price</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Loading…
                        </td>
                    </tr>
                    <tr v-else-if="!filtered.length">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No products found.
                        </td>
                    </tr>
                    <tr
                        v-for="product in filtered"
                        :key="product.id"
                        class="border-t transition-colors odd:bg-rose-50 even:bg-blue-50 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ product.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ product.description ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ fmt(product.price) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="openEdit(product)"
                                    >Edit</Button
                                >
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    @click="confirmDelete(product)"
                                    >Delete</Button
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Modal -->
    <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>New Product</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <div>
                    <Label>Name *</Label>
                    <Input v-model="form.name" placeholder="Product name" />
                    <p
                        v-if="formErrors.name"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ formErrors.name }}
                    </p>
                </div>
                <div>
                    <Label>Description</Label>
                    <Textarea
                        v-model="form.description"
                        placeholder="Optional description"
                        class="resize-none"
                        :rows="3"
                    />
                </div>
                <div>
                    <Label>Price (€)</Label>
                    <Input
                        v-model="form.price"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                    />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showCreateModal = false"
                    >Cancel</Button
                >
                <Button :disabled="saving" @click="submitCreate">{{
                    saving ? 'Saving…' : 'Create'
                }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog :open="showEditModal" @update:open="showEditModal = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Product</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <div>
                    <Label>Name *</Label>
                    <Input v-model="editForm.name" />
                    <p
                        v-if="formErrors.name"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ formErrors.name }}
                    </p>
                </div>
                <div>
                    <Label>Description</Label>
                    <Textarea
                        v-model="editForm.description"
                        class="resize-none"
                        :rows="3"
                    />
                </div>
                <div>
                    <Label>Price (€)</Label>
                    <Input v-model="editForm.price" type="number" step="0.01" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showEditModal = false"
                    >Cancel</Button
                >
                <Button :disabled="saving" @click="submitEdit">{{
                    saving ? 'Saving…' : 'Save Changes'
                }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Delete Product</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <span class="font-semibold">{{ productToDelete?.name }}</span
                >? This action cannot be undone.
            </p>
            <DialogFooter class="flex justify-end gap-2">
                <Button variant="outline" @click="showDeleteModal = false"
                    >Cancel</Button
                >
                <Button
                    variant="destructive"
                    :disabled="deleting"
                    @click="deleteProduct"
                    >{{ deleting ? 'Deleting…' : 'Delete' }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
