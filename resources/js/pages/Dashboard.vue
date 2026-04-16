<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// ─── Types ────────────────────────────────────────────────────────────────────
interface StageRow {
    stage: string;
    count: number;
    total_value: number;
}

interface RecentDeal {
    id: number;
    title: string;
    stage: string;
    value: number;
    entity: { id: number; name: string } | null;
    created_at: string;
}

interface UpcomingEvent {
    id: number;
    title: string;
    start_at: string;
    all_day: boolean;
}

interface DashboardStats {
    entities: number;
    people: number;
    open_deals: number;
    pipeline_value: number;
    won_deals_30d: number;
    won_value_30d: number;
    deals_by_stage: StageRow[];
    recent_deals: RecentDeal[];
    upcoming_events: UpcomingEvent[];
}

// ─── State ────────────────────────────────────────────────────────────────────
const stats = ref<DashboardStats | null>(null);
const loading = ref(true);

// ─── Fetch ────────────────────────────────────────────────────────────────────
async function fetchStats() {
    loading.value = true;

    try {
        const { data } = await axios.get<DashboardStats>('/api/dashboard');
        stats.value = data;
    } catch {
        // silently handled — show empty/zero values
    } finally {
        loading.value = false;
    }
}

onMounted(fetchStats);

// ─── Helpers ─────────────────────────────────────────────────────────────────
const STAGE_LABELS: Record<string, string> = {
    lead: 'Lead',
    contact: 'Contacted',
    proposal: 'Proposal',
    negotiation: 'Negotiation',
    won: 'Won',
    lost: 'Lost',
};

const STAGE_VARIANTS: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    lead: 'secondary',
    contact: 'secondary',
    proposal: 'default',
    negotiation: 'default',
    won: 'default',
    lost: 'destructive',
};

const STAGE_BAR_COLORS: Record<string, string> = {
    lead: 'bg-slate-400',
    contact: 'bg-blue-400',
    proposal: 'bg-yellow-400',
    negotiation: 'bg-orange-400',
    won: 'bg-green-500',
    lost: 'bg-red-400',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const totalDeals = computed(
    () => stats.value?.deals_by_stage.reduce((sum, s) => sum + s.count, 0) ?? 0,
);

const orderedStages = computed(() => {
    const order = ['lead', 'contact', 'proposal', 'negotiation', 'won', 'lost'];
    const map = Object.fromEntries(
        (stats.value?.deals_by_stage ?? []).map((s) => [s.stage, s]),
    );

    return order
        .map((stage) => map[stage] ?? { stage, count: 0, total_value: 0 })
        .filter(
            (s) =>
                s.count > 0 || (stats.value?.deals_by_stage ?? []).length > 0,
        );
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="crm-table-container flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4"
        style="background-color: #ece4d9"
    >
        <!-- KPI Cards -->
        <div
            class="crm-table-container grid gap-4 md:grid-cols-2 lg:grid-cols-4"
        >
            <!-- Entities -->
            <Card class="crm-table-container">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Entities</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">
                        <span
                            v-if="loading"
                            class="animate-pulse text-muted-foreground"
                            >…</span
                        >
                        <span v-else>{{ stats?.entities ?? 0 }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        <Link href="/entities" class="hover:underline"
                            >View all companies →</Link
                        >
                    </p>
                </CardContent>
            </Card>

            <!-- People -->
            <Card class="crm-table-container">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >People</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">
                        <span
                            v-if="loading"
                            class="animate-pulse text-muted-foreground"
                            >…</span
                        >
                        <span v-else>{{ stats?.people ?? 0 }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        <Link href="/people" class="hover:underline"
                            >View all contacts →</Link
                        >
                    </p>
                </CardContent>
            </Card>

            <!-- Pipeline -->
            <Card class="crm-table-container">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Open Pipeline</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">
                        <span
                            v-if="loading"
                            class="animate-pulse text-muted-foreground"
                            >…</span
                        >
                        <span v-else>{{
                            formatCurrency(stats?.pipeline_value ?? 0)
                        }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ stats?.open_deals ?? 0 }} open deal{{
                            (stats?.open_deals ?? 0) !== 1 ? 's' : ''
                        }}
                        ·
                        <Link href="/deals" class="hover:underline"
                            >View deals →</Link
                        >
                    </p>
                </CardContent>
            </Card>

            <!-- Won (30 days) -->
            <Card class="crm-table-container">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground"
                        >Won Last 30 Days</CardTitle
                    >
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">
                        <span
                            v-if="loading"
                            class="animate-pulse text-muted-foreground"
                            >…</span
                        >
                        <span v-else>{{
                            formatCurrency(stats?.won_value_30d ?? 0)
                        }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ stats?.won_deals_30d ?? 0 }} deal{{
                            (stats?.won_deals_30d ?? 0) !== 1 ? 's' : ''
                        }}
                        closed
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Deal Pipeline + Activity -->
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Deal Stage Distribution -->
            <Card class="crm-table-container">
                <CardHeader>
                    <CardTitle class="text-base">Deal Pipeline</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="loading" class="space-y-2">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="h-6 animate-pulse rounded bg-muted"
                        />
                    </div>
                    <div
                        v-else-if="!stats?.deals_by_stage?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No deals yet.
                        <Link href="/deals" class="hover:underline"
                            >Create one →</Link
                        >
                    </div>
                    <template v-else>
                        <div
                            v-for="row in orderedStages"
                            :key="row.stage"
                            class="space-y-1"
                        >
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="font-medium">{{
                                    STAGE_LABELS[row.stage] ?? row.stage
                                }}</span>
                                <span class="text-muted-foreground">
                                    {{ row.count }} deal{{
                                        row.count !== 1 ? 's' : ''
                                    }}
                                    · {{ formatCurrency(row.total_value) }}
                                </span>
                            </div>
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    :class="
                                        STAGE_BAR_COLORS[row.stage] ??
                                        'bg-primary'
                                    "
                                    class="h-2 rounded-full transition-all"
                                    :style="{
                                        width:
                                            totalDeals > 0
                                                ? `${(row.count / totalDeals) * 100}%`
                                                : '0%',
                                    }"
                                />
                            </div>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <!-- Recent Deals -->
            <Card class="crm-table-container">
                <CardHeader>
                    <CardTitle class="text-base">Recent Deals</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="space-y-2">
                        <div
                            v-for="i in 5"
                            :key="i"
                            class="h-10 animate-pulse rounded bg-muted"
                        />
                    </div>
                    <div
                        v-else-if="!stats?.recent_deals?.length"
                        class="text-sm text-muted-foreground"
                    >
                        No deals yet.
                        <Link href="/deals" class="hover:underline"
                            >Create one →</Link
                        >
                    </div>
                    <ul v-else class="divide-y">
                        <li
                            v-for="deal in stats.recent_deals"
                            :key="deal.id"
                            class="flex items-center justify-between py-2 text-sm"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ deal.title }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ deal.entity?.name ?? 'No company' }}
                                </p>
                            </div>
                            <div
                                class="ml-4 flex shrink-0 flex-col items-end gap-1"
                            >
                                <Badge
                                    :variant="
                                        STAGE_VARIANTS[deal.stage] ?? 'outline'
                                    "
                                    class="text-xs"
                                >
                                    {{ STAGE_LABELS[deal.stage] ?? deal.stage }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">{{
                                    formatCurrency(deal.value)
                                }}</span>
                            </div>
                        </li>
                    </ul>
                    <div v-if="!loading" class="mt-3 text-right text-xs">
                        <Link
                            href="/deals"
                            class="text-muted-foreground hover:underline"
                            >View all deals →</Link
                        >
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Upcoming Events -->
        <Card class="crm-table-container">
            <CardHeader>
                <CardTitle class="text-base"
                    >Upcoming Calendar Events</CardTitle
                >
            </CardHeader>
            <CardContent>
                <div v-if="loading" class="space-y-2">
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="h-8 animate-pulse rounded bg-muted"
                    />
                </div>
                <div
                    v-else-if="!stats?.upcoming_events?.length"
                    class="text-sm text-muted-foreground"
                >
                    No upcoming events.
                    <Link href="/calendar" class="hover:underline"
                        >Open calendar →</Link
                    >
                </div>
                <ul v-else class="divide-y">
                    <li
                        v-for="event in stats.upcoming_events"
                        :key="event.id"
                        class="flex items-center justify-between py-2 text-sm"
                    >
                        <span class="font-medium">{{ event.title }}</span>
                        <span class="text-xs text-muted-foreground">
                            {{
                                event.all_day
                                    ? 'All day'
                                    : formatDate(event.start_at)
                            }}
                        </span>
                    </li>
                </ul>
                <div v-if="!loading" class="mt-3 text-right text-xs">
                    <Link
                        href="/calendar"
                        class="text-muted-foreground hover:underline"
                        >View calendar →</Link
                    >
                </div>
            </CardContent>
        </Card>
    </div>
</template>
