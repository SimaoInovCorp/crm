<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
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
import { useCsvExport } from '@/composables/useCsvExport';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products', href: '/products/statistics' },
            { title: 'Statistics', href: '/products/statistics' },
        ],
    },
});

interface ProductStat {
    product_id: number;
    product_name: string;
    frequency: number;
    total_value: number;
}

interface DrillDeal {
    id: number;
    title: string;
    stage: string;
    value: number;
    expected_close_date: string | null;
    quantity: number;
    unit_price: number;
    line_total: number;
    entity: { id: number; name: string } | null;
    owner: { id: number; name: string } | null;
}

const STAGES = ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

const STAGE_VARIANTS: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    lead: 'secondary',
    qualified: 'outline',
    proposal: 'default',
    negotiation: 'default',
    won: 'default',
    lost: 'destructive',
};

// ─── State ────────────────────────────────────────────────────────────────────
const stats = ref<ProductStat[]>([]);
const loading = ref(true);

const filters = reactive({
    date_from: '',
    date_to: '',
    stage: '',
    owner_id: '',
});

// Drill-down
const drillOpen = ref(false);
const drillProduct = ref<ProductStat | null>(null);
const drillDeals = ref<DrillDeal[]>([]);
const drillLoading = ref(false);

// ─── Totals ───────────────────────────────────────────────────────────────────
const totalFrequency = computed(() =>
    stats.value.reduce((s, r) => s + r.frequency, 0),
);
const totalValue = computed(() =>
    stats.value.reduce((s, r) => s + r.total_value, 0),
);

// ─── API Calls ────────────────────────────────────────────────────────────────
async function fetchStats() {
    loading.value = true;

    try {
        const params: Record<string, string> = {};

        if (filters.date_from) {
            params.date_from = filters.date_from;
        }

        if (filters.date_to) {
            params.date_to = filters.date_to;
        }

        if (filters.stage) {
            params.stage = filters.stage;
        }

        if (filters.owner_id) {
            params.owner_id = filters.owner_id;
        }

        const { data } = await axios.get('/api/products/statistics', {
            params,
        });
        stats.value = data.data;
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    filters.date_from = '';
    filters.date_to = '';
    filters.stage = '';
    filters.owner_id = '';
}

watch(filters, () => fetchStats(), { deep: true });

async function openDrillDown(row: ProductStat) {
    drillProduct.value = row;
    drillDeals.value = [];
    drillOpen.value = true;
    drillLoading.value = true;

    try {
        const params: Record<string, string> = {};

        if (filters.date_from) {
            params.date_from = filters.date_from;
        }

        if (filters.date_to) {
            params.date_to = filters.date_to;
        }

        if (filters.stage) {
            params.stage = filters.stage;
        }

        if (filters.owner_id) {
            params.owner_id = filters.owner_id;
        }

        const { data } = await axios.get(
            `/api/products/${row.product_id}/drill-down`,
            { params },
        );
        drillDeals.value = data.data;
    } finally {
        drillLoading.value = false;
    }
}

// ─── CSV Export ───────────────────────────────────────────────────────────────
function exportCsv() {
    downloadCsv(
        'product-statistics.csv',
        ['Product', 'Deals (Frequency)', 'Total Value (EUR)'],
        stats.value.map((r) => [
            r.product_name,
            r.frequency,
            r.total_value.toFixed(2),
        ]),
    );
}

function exportDrillCsv() {
    downloadCsv(
        `deals-${drillProduct.value?.product_name ?? 'product'}.csv`,
        [
            'Deal',
            'Entity',
            'Owner',
            'Stage',
            'Qty',
            'Unit Price (EUR)',
            'Line Total (EUR)',
            'Close Date',
        ],
        drillDeals.value.map((d) => [
            d.title,
            d.entity?.name ?? '',
            d.owner?.name ?? '',
            d.stage,
            d.quantity,
            d.unit_price.toFixed(2),
            d.line_total.toFixed(2),
            d.expected_close_date ?? '',
        ]),
    );
}

function fmt(n: number) {
    return new Intl.NumberFormat('en-IE', {
        style: 'currency',
        currency: 'EUR',
    }).format(n);
}

const { downloadCsv } = useCsvExport();

onMounted(fetchStats);
</script>

<template>
    <Head title="Product Statistics" />

    <div
        class="crm-table-container space-y-6 p-6"
        style="background-color: #ece4d9"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Product Statistics</h1>
            <Button
                @click="exportCsv"
                variant="outline"
                :disabled="!stats.length"
            >
                Export CSV
            </Button>
        </div>

        <!-- Filters -->
        <div
            class="crm-table-container grid grid-cols-2 gap-4 rounded-lg bg-muted/40 p-4 md:grid-cols-4"
        >
            <div>
                <Label>Date From</Label>
                <Input type="date" v-model="filters.date_from" />
            </div>
            <div>
                <Label>Date To</Label>
                <Input type="date" v-model="filters.date_to" />
            </div>
            <div>
                <Label>Stage</Label>
                <Select v-model="filters.stage">
                    <SelectTrigger>
                        <SelectValue placeholder="All stages" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in STAGES" :key="s" :value="s">
                            {{ s.charAt(0).toUpperCase() + s.slice(1) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-end gap-2">
                <Button @click="clearFilters" variant="ghost">Clear</Button>
            </div>
        </div>

        <!-- Table -->
        <div class="crm-table-container">
            <table class="w-full text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Product</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Deals (Frequency)
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Total Value
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td
                            colspan="4"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            <span
                                class="crm-spinner mx-auto block opacity-60"
                            />
                        </td>
                    </tr>
                    <tr v-else-if="!stats.length">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No data for the selected filters.
                        </td>
                    </tr>
                    <tr
                        v-for="row in stats"
                        :key="row.product_id"
                        class="border-t transition-colors odd:bg-rose-50 even:bg-blue-50 hover:bg-muted/40"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ row.product_name }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ row.frequency }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ fmt(row.total_value) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="openDrillDown(row)"
                            >
                                View Deals
                            </Button>
                        </td>
                    </tr>
                    <!-- Totals row -->
                    <tr
                        v-if="stats.length"
                        class="border-t bg-muted/40 font-semibold"
                    >
                        <td class="px-4 py-3">Totals</td>
                        <td class="px-4 py-3 text-right">
                            {{ totalFrequency }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ fmt(totalValue) }}
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Drill-down dialog -->
    <Dialog :open="drillOpen" @update:open="drillOpen = $event">
        <DialogContent class="w-full max-w-3xl">
            <DialogHeader>
                <DialogTitle>
                    Deals — {{ drillProduct?.product_name }}
                </DialogTitle>
            </DialogHeader>

            <div
                v-if="drillLoading"
                class="py-8 text-center text-muted-foreground"
            >
                Loading…
            </div>
            <template v-else>
                <div class="mb-3 flex justify-end">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="exportDrillCsv"
                        :disabled="!drillDeals.length"
                    >
                        Export CSV
                    </Button>
                </div>
                <div class="overflow-x-auto rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/60">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">
                                    Deal
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    Entity
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    Stage
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Qty
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Unit Price
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Line Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!drillDeals.length">
                                <td
                                    colspan="6"
                                    class="px-3 py-6 text-center text-muted-foreground"
                                >
                                    No deals found.
                                </td>
                            </tr>
                            <tr
                                v-for="deal in drillDeals"
                                :key="deal.id"
                                class="border-t transition-colors hover:bg-muted/20"
                            >
                                <td class="px-3 py-2">
                                    <a
                                        :href="`/deals/${deal.id}`"
                                        class="text-primary hover:underline"
                                    >
                                        {{ deal.title }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 text-muted-foreground">
                                    {{ deal.entity?.name ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    <Badge
                                        :variant="
                                            STAGE_VARIANTS[deal.stage] ??
                                            'secondary'
                                        "
                                    >
                                        {{ deal.stage }}
                                    </Badge>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    {{ deal.quantity }}
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    {{ fmt(deal.unit_price) }}
                                </td>
                                <td
                                    class="px-3 py-2 text-right font-medium tabular-nums"
                                >
                                    {{ fmt(deal.line_total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </DialogContent>
    </Dialog>
</template>
