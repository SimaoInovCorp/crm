<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue';
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
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Deals', href: '/deals' }] },
});

const STAGES = [
    'lead',
    'contact',
    'proposal',
    'negotiation',
    'won',
    'lost',
] as const;
type Stage = (typeof STAGES)[number];

const STAGE_LABELS: Record<Stage, string> = {
    lead: 'Lead',
    contact: 'Contacted',
    proposal: 'Proposal',
    negotiation: 'Negotiation',
    won: 'Won',
    lost: 'Lost',
};

const STAGE_COLORS: Record<Stage, string> = {
    lead: 'bg-slate-100 dark:bg-slate-800',
    contact: 'bg-blue-50 dark:bg-blue-950',
    proposal: 'bg-yellow-50 dark:bg-yellow-950',
    negotiation: 'bg-orange-50 dark:bg-orange-950',
    won: 'bg-green-50 dark:bg-green-950',
    lost: 'bg-red-50 dark:bg-red-950',
};

const STAGE_BORDER_COLORS: Record<Stage, string> = {
    lead: 'border-slate-300 dark:border-slate-700',
    contact: 'border-blue-300 dark:border-blue-800',
    proposal: 'border-yellow-300 dark:border-yellow-800',
    negotiation: 'border-orange-300 dark:border-orange-800',
    won: 'border-green-300 dark:border-green-800',
    lost: 'border-red-300 dark:border-red-800',
};

const STAGE_RING_COLORS: Record<Stage, string> = {
    lead: 'ring-2 ring-slate-200 dark:ring-slate-700',
    contact: 'ring-2 ring-blue-200 dark:ring-blue-900',
    proposal: 'ring-2 ring-yellow-200 dark:ring-yellow-900',
    negotiation: 'ring-2 ring-orange-200 dark:ring-orange-900',
    won: 'ring-2 ring-green-200 dark:ring-green-900',
    lost: 'ring-2 ring-red-200 dark:ring-red-900',
};

interface Entity {
    id: number;
    name: string;
}
interface Person {
    id: number;
    name: string;
    entity_id: number | null;
    email: string | null;
}
interface Deal {
    id: number;
    title: string;
    value: string;
    stage: Stage;
    probability: number;
    expected_close_date: string | null;
    entity: Entity;
    owner: { id: number; name: string } | null;
}

interface Product {
    id: number;
    name: string;
    price: string | null;
}

interface DealProductLine {
    product_id: string;
    quantity: number;
    unit_price: number;
}

interface DealDetail extends Deal {
    notes: string | null;
    person: { id: number; name: string } | null;
    products: Array<{
        id: number;
        name: string;
        quantity: number;
        unit_price: string;
    }>;
}

const allDeals = ref<Deal[]>([]);
const entities = ref<Entity[]>([]);
const allPeople = ref<Person[]>([]);
const allProducts = ref<Product[]>([]);
const loading = ref(false);
const draggingDeal = ref<Deal | null>(null);
const dragOverStage = ref<Stage | null>(null);

const search = ref('');
const stageFilter = ref<Stage | ''>('');

// ─── Create modal ─────────────────────────────────────────────────────────────
const showCreateModal = ref(false);
const saving = ref(false);
const formErrors = ref<Record<string, string>>({});
const form = ref({
    title: '',
    entity_id: '',
    person_id: '',
    value: '',
    stage: 'lead' as Stage,
    expected_close_date: '',
    notes: '',
});
const formProducts = ref<DealProductLine[]>([]);

const formProductsTotal = computed(() =>
    formProducts.value.reduce((s, p) => s + p.quantity * p.unit_price, 0),
);

function addProductLine() {
    formProducts.value.push({ product_id: '', quantity: 1, unit_price: 0 });
}
function removeProductLine(idx: number) {
    formProducts.value.splice(idx, 1);
}
function onProductSelect(idx: number, productId: string) {
    formProducts.value[idx].product_id = productId;
    const p = allProducts.value.find((x) => String(x.id) === productId);
    if (p && p.price) {
        formProducts.value[idx].unit_price = parseFloat(p.price);
    }
}

// People filtered by selected entity
const entityPeople = computed(() =>
    form.value.entity_id
        ? allPeople.value.filter(
              (p) => p.entity_id === parseInt(form.value.entity_id),
          )
        : allPeople.value,
);

// Reset person when entity changes
watch(
    () => form.value.entity_id,
    () => {
        form.value.person_id = '';
    },
);

const filtered = computed(() => {
    let list = allDeals.value;

    if (search.value) {
        list = list.filter((d) =>
            d.title.toLowerCase().includes(search.value.toLowerCase()),
        );
    }

    if (stageFilter.value) {
        list = list.filter((d) => d.stage === stageFilter.value);
    }

    return list;
});

function dealsByStage(stage: Stage) {
    return filtered.value.filter((d) => d.stage === stage);
}

function stageTotal(stage: Stage) {
    return dealsByStage(stage).reduce(
        (sum, d) => sum + parseFloat(d.value || '0'),
        0,
    );
}

async function fetchDeals() {
    loading.value = true;

    try {
        const { data } = await axios.get('/api/deals', {
            params: { per_page: 200 },
        });
        allDeals.value = data.data;
    } finally {
        loading.value = false;
    }
}

async function fetchEntities() {
    const { data } = await axios.get('/api/entities', {
        params: { per_page: 200 },
    });
    entities.value = data.data;
}

async function fetchAllPeople() {
    const { data } = await axios.get('/api/people', {
        params: { per_page: 500 },
    });
    allPeople.value = data.data;
}

async function fetchAllProducts() {
    const { data } = await axios.get('/api/products', {
        params: { per_page: 500 },
    });
    allProducts.value = data.data;
}

onMounted(() => {
    fetchDeals();
    fetchEntities();
    fetchAllPeople();
    fetchAllProducts();
});

function exportCsv() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (stageFilter.value) params.set('stage', stageFilter.value);
    window.location.href = `/api/deals/export?${params.toString()}`;
}

// ─── Drag & Drop ──────────────────────────────────────────────────────────────
function onDragStart(deal: Deal) {
    draggingDeal.value = deal;
}
function onDragOver(stage: Stage) {
    dragOverStage.value = stage;
}
function onDragEnd() {
    draggingDeal.value = null;
    dragOverStage.value = null;
}

async function onDrop(stage: Stage) {
    if (!draggingDeal.value || draggingDeal.value.stage === stage) {
        onDragEnd();

        return;
    }

    const deal = draggingDeal.value;
    deal.stage = stage;
    onDragEnd();

    try {
        await axios.patch(`/api/deals/${deal.id}/stage`, { stage });
        await fetchDeals();
    } catch {
        await fetchDeals();
    }
}

// ─── Create Deal ─────────────────────────────────────────────────────────────
function openCreate() {
    form.value = {
        title: '',
        entity_id: '',
        person_id: '',
        value: '',
        stage: 'lead',
        expected_close_date: '',
        notes: '',
    };
    formProducts.value = [];
    formErrors.value = {};
    showCreateModal.value = true;
}

async function submitCreate() {
    saving.value = true;
    formErrors.value = {};

    const validProducts = formProducts.value.filter((p) => p.product_id);

    try {
        await axios.post('/api/deals', {
            ...form.value,
            products: validProducts.map((p) => ({
                product_id: parseInt(p.product_id),
                quantity: p.quantity,
                unit_price: p.unit_price,
            })),
        });
        showCreateModal.value = false;
        await fetchDeals();
    } catch (err: any) {
        if (err.response?.status === 422) {
            const errs = err.response.data.errors as Record<string, string[]>;
            Object.keys(errs).forEach(
                (k) => (formErrors.value[k] = errs[k][0]),
            );
        }
    } finally {
        saving.value = false;
    }
}

// ─── Deal Detail Modal ────────────────────────────────────────────────────────
const showDetailModal = ref(false);
const detailDeal = ref<DealDetail | null>(null);
const loadingDetail = ref(false);
const detailError = ref<string | null>(null);

async function openDealDetail(dealId: number) {
    detailDeal.value = null;
    detailError.value = null;
    showDetailModal.value = true;
    loadingDetail.value = true;

    try {
        const { data } = await axios.get(`/api/deals/${dealId}`);
        detailDeal.value = data.data;
    } catch (err: any) {
        detailError.value =
            err.response?.data?.message ?? 'Failed to load deal.';
    } finally {
        loadingDetail.value = false;
    }
}

// ─── Delete Deal ──────────────────────────────────────────────────────────────
const deletingDealId = ref<number | null>(null);
const showDeleteModal = ref(false);
const dealToDelete = ref<Deal | null>(null);

function confirmDeleteDeal(deal: Deal) {
    dealToDelete.value = deal;
    showDeleteModal.value = true;
}

async function deleteDeal() {
    if (!dealToDelete.value) return;
    const deal = dealToDelete.value;
    deletingDealId.value = deal.id;
    showDeleteModal.value = false;

    try {
        await axios.delete(`/api/deals/${deal.id}`);
        allDeals.value = allDeals.value.filter((d) => d.id !== deal.id);

        if (showDetailModal.value && detailDeal.value?.id === deal.id) {
            showDetailModal.value = false;
        }
    } finally {
        deletingDealId.value = null;
        dealToDelete.value = null;
    }
}
</script>

<template>
    <Head title="Deals" />
    <div
        class="crm-table-container space-y-4 p-6"
        style="background-color: #ece4d9"
    >
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Deals — Kanban</h1>
            <div class="flex gap-2">
                <Button variant="outline" @click="exportCsv">Export CSV</Button>
                <Button @click="openCreate">+ New Deal</Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="crm-table-container flex gap-3">
            <Input
                v-model="search"
                placeholder="Search deals…"
                class="max-w-xs"
            />
            <Select v-model="stageFilter">
                <SelectTrigger class="w-40"
                    ><SelectValue placeholder="All Stages"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All Stages</SelectItem>
                    <SelectItem v-for="s in STAGES" :key="s" :value="s">{{
                        STAGE_LABELS[s]
                    }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Kanban Board -->
        <div v-if="loading" class="flex items-center justify-center py-16">
            <span class="crm-spinner crm-spinner-lg opacity-60" />
        </div>
        <div v-else class="crm-table-container flex gap-1 overflow-x-auto pb-4">
            <div
                v-for="stage in STAGES"
                :key="stage"
                class="w-45 flex-shrink-0 space-y-2 rounded-lg border p-3"
                :class="[
                    STAGE_COLORS[stage],
                    STAGE_BORDER_COLORS[stage],
                    STAGE_RING_COLORS[stage],
                    dragOverStage === stage ? 'ring-2 ring-primary' : '',
                ]"
                @dragover.prevent="onDragOver(stage)"
                @drop.prevent="onDrop(stage)"
            >
                <div class="flex items-center justify-between px-1">
                    <span
                        class="rounded border px-2 py-1 text-sm font-semibold"
                        :class="[
                            STAGE_COLORS[stage],
                            STAGE_BORDER_COLORS[stage],
                        ]"
                    >
                        {{ STAGE_LABELS[stage] }}
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ dealsByStage(stage).length }} · €{{
                            stageTotal(stage).toLocaleString('pt-PT', {
                                minimumFractionDigits: 0,
                            })
                        }}
                    </span>
                </div>

                <div
                    v-for="deal in dealsByStage(stage)"
                    :key="deal.id"
                    draggable="true"
                    class="cursor-grab rounded-md border bg-background p-3 shadow-sm transition-shadow hover:shadow-md"
                    :class="[
                        STAGE_BORDER_COLORS[stage],
                        STAGE_RING_COLORS[stage],
                    ]"
                    @dragstart="onDragStart(deal)"
                    @dragend="onDragEnd"
                >
                    <div class="flex items-start justify-between gap-1">
                        <button
                            type="button"
                            class="text-left text-sm font-medium hover:underline"
                            @click.stop="openDealDetail(deal.id)"
                        >
                            {{ deal.title }}
                        </button>
                        <button
                            type="button"
                            class="shrink-0 rounded p-0.5 text-muted-foreground hover:text-destructive"
                            :disabled="deletingDealId === deal.id"
                            :title="`Delete ${deal.title}`"
                            @click.stop="confirmDeleteDeal(deal)"
                        >
                            ✕
                        </button>
                    </div>
                    <div class="mt-1 text-xs text-muted-foreground">
                        {{ deal.entity?.name }}
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-sm font-semibold"
                            >€{{
                                parseFloat(deal.value).toLocaleString('pt-PT', {
                                    minimumFractionDigits: 0,
                                })
                            }}</span
                        >
                        <Badge variant="outline">{{ deal.probability }}%</Badge>
                    </div>
                    <div
                        v-if="deal.expected_close_date"
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Close: {{ deal.expected_close_date }}
                    </div>
                </div>

                <div
                    v-if="dealsByStage(stage).length === 0"
                    class="py-4 text-center text-xs text-muted-foreground"
                >
                    No deals
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="showCreateModal" @update:open="showCreateModal = $event">
            <DialogContent>
                <DialogHeader><DialogTitle>New Deal</DialogTitle></DialogHeader>
                <div class="space-y-4">
                    <div>
                        <Label>Title *</Label>
                        <Input v-model="form.title" placeholder="Deal title" />
                        <p
                            v-if="formErrors.title"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ formErrors.title }}
                        </p>
                    </div>
                    <div>
                        <Label>Entity *</Label>
                        <Select v-model="form.entity_id">
                            <SelectTrigger
                                ><SelectValue placeholder="Select entity"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="e in entities"
                                    :key="e.id"
                                    :value="String(e.id)"
                                    >{{ e.name }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <p
                            v-if="formErrors.entity_id"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ formErrors.entity_id }}
                        </p>
                    </div>
                    <div>
                        <Label>Contact (Person)</Label>
                        <Select v-model="form.person_id">
                            <SelectTrigger
                                ><SelectValue
                                    placeholder="Select person (optional)"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">None</SelectItem>
                                <SelectItem
                                    v-for="p in entityPeople"
                                    :key="p.id"
                                    :value="String(p.id)"
                                    >{{ p.name }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Value (€)</Label>
                        <Input
                            v-model="form.value"
                            type="number"
                            placeholder="0"
                        />
                    </div>
                    <div>
                        <Label>Stage</Label>
                        <Select v-model="form.stage">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="s in STAGES"
                                    :key="s"
                                    :value="s"
                                    >{{ STAGE_LABELS[s] }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Expected Close Date</Label>
                        <Input v-model="form.expected_close_date" type="date" />
                    </div>
                    <div>
                        <Label>Notes</Label>
                        <Textarea
                            v-model="form.notes"
                            placeholder="Internal notes about this deal…"
                            class="resize-none"
                            :rows="3"
                        />
                    </div>
                    <!-- Products -->
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <Label>Products</Label>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="addProductLine"
                            >
                                + Add Product
                            </Button>
                        </div>
                        <div
                            v-for="(line, idx) in formProducts"
                            :key="idx"
                            class="mb-2 grid grid-cols-12 items-end gap-1"
                        >
                            <div class="col-span-5">
                                <Select
                                    :model-value="line.product_id"
                                    @update:model-value="
                                        (v) => onProductSelect(idx, v)
                                    "
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Product" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="p in allProducts"
                                            :key="p.id"
                                            :value="String(p.id)"
                                            >{{ p.name }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="col-span-2">
                                <Input
                                    v-model.number="line.quantity"
                                    type="number"
                                    min="1"
                                    placeholder="Qty"
                                />
                            </div>
                            <div class="col-span-3">
                                <Input
                                    v-model.number="line.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="Unit €"
                                />
                            </div>
                            <div class="col-span-2 text-right">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="text-destructive"
                                    @click="removeProductLine(idx)"
                                    >✕</Button
                                >
                            </div>
                        </div>
                        <p
                            v-if="formProducts.length"
                            class="mt-1 text-right text-xs font-medium"
                        >
                            Products Total: €{{
                                formProductsTotal.toLocaleString('pt-PT', {
                                    minimumFractionDigits: 2,
                                })
                            }}
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCreateModal = false"
                        >Cancel</Button
                    >
                    <Button :disabled="saving" @click="submitCreate">{{
                        saving ? 'Saving…' : 'Create Deal'
                    }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Deal Detail Modal -->
        <Dialog :open="showDetailModal" @update:open="showDetailModal = $event">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{
                        detailDeal?.title ?? 'Deal Details'
                    }}</DialogTitle>
                </DialogHeader>
                <div
                    v-if="loadingDetail"
                    class="py-6 text-center text-muted-foreground"
                >
                    Loading…
                </div>
                <p
                    v-else-if="detailError"
                    class="py-4 text-sm text-destructive"
                >
                    {{ detailError }}
                </p>
                <div v-else-if="detailDeal" class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-muted-foreground">Entity</p>
                            <p class="font-medium">
                                {{ detailDeal.entity?.name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Stage</p>
                            <Badge>{{ STAGE_LABELS[detailDeal.stage] }}</Badge>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Value</p>
                            <p class="font-semibold">
                                €{{
                                    parseFloat(detailDeal.value).toLocaleString(
                                        'pt-PT',
                                        { minimumFractionDigits: 0 },
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Probability
                            </p>
                            <p>{{ detailDeal.probability }}%</p>
                        </div>
                        <div v-if="detailDeal.person">
                            <p class="text-xs text-muted-foreground">Contact</p>
                            <p>{{ detailDeal.person.name }}</p>
                        </div>
                        <div v-if="detailDeal.owner">
                            <p class="text-xs text-muted-foreground">Owner</p>
                            <p>{{ detailDeal.owner.name }}</p>
                        </div>
                        <div
                            v-if="detailDeal.expected_close_date"
                            class="col-span-2"
                        >
                            <p class="text-xs text-muted-foreground">
                                Expected Close
                            </p>
                            <p>{{ detailDeal.expected_close_date }}</p>
                        </div>
                    </div>
                    <div
                        v-if="detailDeal.notes"
                        class="rounded bg-muted/40 p-3"
                    >
                        <p class="mb-1 text-xs text-muted-foreground">Notes</p>
                        <p class="whitespace-pre-line">
                            {{ detailDeal.notes }}
                        </p>
                    </div>
                    <div v-if="detailDeal.products?.length">
                        <p class="mb-1 text-xs text-muted-foreground">
                            Products
                        </p>
                        <div
                            v-for="p in detailDeal.products"
                            :key="p.id"
                            class="flex items-center justify-between border-t py-1 text-xs"
                        >
                            <span>{{ p.name }} ×{{ p.quantity }}</span>
                            <span
                                >€{{
                                    parseFloat(p.unit_price).toLocaleString(
                                        'pt-PT',
                                        { minimumFractionDigits: 2 },
                                    )
                                }}</span
                            >
                        </div>
                    </div>
                </div>
                <DialogFooter class="flex justify-between">
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="deletingDealId === detailDeal?.id"
                        @click="detailDeal && confirmDeleteDeal(detailDeal)"
                        >Delete</Button
                    >
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="showDetailModal = false"
                            >Close</Button
                        >
                        <Button
                            v-if="detailDeal"
                            size="sm"
                            as="a"
                            :href="`/deals/${detailDeal.id}`"
                            >Full View →</Button
                        >
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- Delete Confirmation Modal -->
        <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
            <DialogContent class="max-w-sm">
                <DialogHeader>
                    <DialogTitle>Delete Deal</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete
                    <span class="font-semibold">{{ dealToDelete?.title }}</span
                    >? This action cannot be undone.
                </p>
                <DialogFooter class="flex justify-end gap-2">
                    <Button variant="outline" @click="showDeleteModal = false"
                        >Cancel</Button
                    >
                    <Button variant="destructive" @click="deleteDeal"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
