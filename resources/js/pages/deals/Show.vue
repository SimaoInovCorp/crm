<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useProductLines } from '@/composables/useProductLines';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Deals', href: '/deals' },
            { title: 'Detail', href: '#' },
        ],
    },
});

// Extract deal ID from the URL (e.g. /deals/42 → "42")
const dealId = window.location.pathname.split('/').pop();

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

const STAGE_BADGE: Record<
    Stage,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    lead: 'secondary',
    contact: 'outline',
    proposal: 'default',
    negotiation: 'default',
    won: 'default',
    lost: 'destructive',
};

const ACTIVITY_TYPES = [
    'note',
    'call',
    'email',
    'meeting',
    'task',
    'other',
] as const;
type ActivityType = (typeof ACTIVITY_TYPES)[number];

const ACTIVITY_TYPE_ICONS: Record<string, string> = {
    note: '📝',
    call: '📞',
    email: '✉️',
    meeting: '🤝',
    stage_change: '🔄',
    task: '✅',
    other: '📌',
};

const ACTIVITY_TYPE_DESCRIPTIONS: Record<string, string> = {
    note: 'Internal note — visible only to your team',
    call: 'Phone call — log a call with this contact',
    email: 'Email — record an email sent or received',
    meeting: 'Meeting — log an in-person or virtual meeting',
    task: 'Task — mark a completed to-do item',
    other: 'Other — any general activity or interaction',
};

interface Deal {
    id: number;
    title: string;
    value: string;
    stage: Stage;
    probability: number;
    expected_close_date: string | null;
    notes: string | null;
    entity: { id: number; name: string } | null;
    person: { id: number; name: string } | null;
    owner: { id: number; name: string } | null;
    products: Array<{
        id: number;
        name: string;
        quantity: number;
        unit_price: string;
    }>;
}

interface ActivityLog {
    id: number;
    type: string;
    description: string;
    created_at: string;
    metadata: Record<string, string> | null;
    user: { id: number; name: string } | null;
}

const deal = ref<Deal | null>(null);
const loading = ref(true);
const updatingStage = ref(false);
const fetchError = ref<string | null>(null);

const timeline = ref<ActivityLog[]>([]);
const timelineLoading = ref(false);
const timelinePage = ref(1);
const timelineLastPage = ref(1);
const timelineTotal = ref(0);
const activityType = ref<ActivityType>('note');
const activityDescription = ref('');
const postingActivity = ref(false);

interface FollowUp {
    id: number;
    status: string;
    emails_sent: number;
    next_send_at: string | null;
    last_sent_at: string | null;
    email_template: { id: number; name: string } | null;
}
interface EmailTemplate {
    id: number;
    name: string;
    subject: string;
    body: string;
}

const followUp = ref<FollowUp | null>(null);
const emailTemplates = ref<EmailTemplate[]>([]);
const selectedTemplateId = ref<number | null>(null);
const startingFollowUp = ref(false);
const cancellingFollowUp = ref(false);
const sendingNow = ref(false);
const sendNowError = ref<string | null>(null);
const sendNowSuccess = ref<string | null>(null);

// Custom email body (prefilled from selected template, editable by user)
const customEmailBody = ref('');

// Preselect the active template body when template changes
const selectedTemplate = computed(() =>
    selectedTemplateId.value
        ? (emailTemplates.value.find(
              (t) => t.id === selectedTemplateId.value,
          ) ?? null)
        : null,
);
watch(selectedTemplate, (t) => {
    if (t) customEmailBody.value = t.body;
});

// People list for contact selector
const allPeople = ref<{ id: number; name: string; entity_id: number | null }[]>(
    [],
);
const editingContact = ref(false);
const selectedPersonId = ref<string>('');

// ─── Products ─────────────────────────────────────────────────────────────
const allProducts = ref<{ id: number; name: string; price: string | null }[]>(
    [],
);
const editingProducts = ref(false);
const syncingProducts = ref(false);

const {
    productLines,
    productLinesTotal,
    addProductLine,
    removeProductLine,
    onProductSelect,
} = useProductLines(allProducts);

function startEditProducts() {
    productLines.value = (deal.value?.products ?? []).map((p) => ({
        product_id: String(p.id),
        quantity: p.quantity,
        unit_price: parseFloat(p.unit_price),
    }));
    editingProducts.value = true;
}
function cancelEditProducts() {
    editingProducts.value = false;
}
async function saveProducts() {
    if (!deal.value) return;
    syncingProducts.value = true;
    try {
        const valid = productLines.value.filter((p) => p.product_id);
        const { data } = await axios.put(`/api/deals/${deal.value.id}`, {
            products: valid.map((p) => ({
                product_id: parseInt(p.product_id),
                quantity: p.quantity,
                unit_price: p.unit_price,
            })),
        });
        deal.value.products = data.data.products ?? [];
        editingProducts.value = false;
    } finally {
        syncingProducts.value = false;
    }
}

async function fetchAllProducts() {
    const { data } = await axios.get('/api/products', {
        params: { per_page: 500 },
    });
    allProducts.value = data.data;
}

async function fetchDeal() {
    loading.value = true;
    fetchError.value = null;

    try {
        const { data } = await axios.get(`/api/deals/${dealId}`);
        deal.value = data.data;
    } catch (err: any) {
        fetchError.value =
            err.response?.data?.message ??
            `Error ${err.response?.status ?? ''}: Unable to load deal.`;
    } finally {
        loading.value = false;
    }
}

async function fetchTimeline(page = 1) {
    timelineLoading.value = true;

    try {
        const { data } = await axios.get(`/api/deals/${dealId}/timeline`, {
            params: { page, per_page: 5 },
        });
        timeline.value = data.data;
        timelinePage.value = data.meta?.current_page ?? page;
        timelineLastPage.value = data.meta?.last_page ?? 1;
        timelineTotal.value = data.meta?.total ?? data.data.length;
    } finally {
        timelineLoading.value = false;
    }
}

function exportTimeline() {
    window.open(`/api/deals/${dealId}/timeline/export`, '_blank');
}

async function updateStage(stage: Stage) {
    if (!deal.value) {
        return;
    }

    updatingStage.value = true;

    try {
        await axios.patch(`/api/deals/${deal.value.id}/stage`, { stage });
        deal.value.stage = stage;
        await fetchTimeline(1);
    } finally {
        updatingStage.value = false;
    }
}

async function postActivity() {
    if (!deal.value || !activityDescription.value.trim()) {
        return;
    }

    postingActivity.value = true;

    try {
        await axios.post('/api/activity-logs', {
            loggable_type: 'App\\Models\\Deal',
            loggable_id: deal.value.id,
            type: activityType.value,
            description: activityDescription.value.trim(),
        });
        activityDescription.value = '';
        await fetchTimeline(1);
    } finally {
        postingActivity.value = false;
    }
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString('pt-PT', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

onMounted(async () => {
    await fetchDeal();
    await Promise.all([
        fetchTimeline(),
        fetchFollowUp(),
        fetchEmailTemplates(),
        fetchAllPeople(),
        fetchAllProducts(),
    ]);
});

async function fetchFollowUp() {
    const { data } = await axios.get(`/api/deals/${dealId}/follow-up`);
    followUp.value = data.data;
}

async function fetchEmailTemplates() {
    const { data } = await axios.get('/api/email-templates');
    emailTemplates.value = data.data;
}

async function fetchAllPeople() {
    const { data } = await axios.get('/api/people', {
        params: { per_page: 500 },
    });
    allPeople.value = data.data;
}

async function startFollowUp() {
    if (!selectedTemplateId.value) {
        return;
    }

    startingFollowUp.value = true;

    try {
        const { data } = await axios.post(`/api/deals/${dealId}/follow-up`, {
            email_template_id: selectedTemplateId.value,
        });
        followUp.value = data.data;
    } finally {
        startingFollowUp.value = false;
    }
}

async function cancelFollowUp() {
    cancellingFollowUp.value = true;

    try {
        await axios.post(`/api/deals/${dealId}/follow-up/cancel`);
        followUp.value = followUp.value
            ? { ...followUp.value, status: 'cancelled' }
            : null;
    } finally {
        cancellingFollowUp.value = false;
    }
}

async function sendNow() {
    sendingNow.value = true;
    sendNowError.value = null;
    sendNowSuccess.value = null;

    try {
        const payload: Record<string, string | number> = {};
        if (selectedTemplateId.value) {
            payload.email_template_id = selectedTemplateId.value;
        }
        if (customEmailBody.value.trim()) {
            payload.body = customEmailBody.value.trim();
        }
        const { data } = await axios.post(
            `/api/deals/${dealId}/follow-up/send-now`,
            payload,
        );
        if (data.data) {
            followUp.value = data.data;
        }
        sendNowSuccess.value = data.message ?? 'Email sent successfully.';
    } catch (err: any) {
        sendNowError.value =
            err.response?.data?.message ?? 'Failed to send email.';
    } finally {
        sendingNow.value = false;
    }
}

async function updateContact() {
    if (!deal.value) return;

    try {
        await axios.put(`/api/deals/${deal.value.id}`, {
            person_id: selectedPersonId.value || null,
        });
        const updatedPerson = selectedPersonId.value
            ? (allPeople.value.find(
                  (p) => p.id === parseInt(selectedPersonId.value),
              ) ?? null)
            : null;
        deal.value.person = updatedPerson
            ? { id: updatedPerson.id, name: updatedPerson.name }
            : null;
        editingContact.value = false;
    } catch {
        /* no-op */
    }
}
</script>

<template>
    <Head :title="deal?.title ?? 'Deal'" />
    <div v-if="loading" class="p-6 text-muted-foreground">Loading…</div>
    <div v-else-if="fetchError" class="space-y-2 p-6">
        <p class="text-destructive">{{ fetchError }}</p>
        <a href="/deals" class="text-sm text-muted-foreground hover:underline"
            >← Back to Deals</a
        >
    </div>
    <div v-else-if="!deal" class="p-6 text-destructive">Deal not found.</div>
    <div v-else class="max-w-3xl space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div>
                <a
                    href="/deals"
                    class="text-sm text-muted-foreground hover:underline"
                    >← Deals</a
                >
                <h1 class="mt-1 text-2xl font-bold">{{ deal.title }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ deal.entity?.name }}
                </p>
            </div>
            <div class="space-y-2 text-right">
                <div class="text-2xl font-bold">
                    €{{
                        parseFloat(deal.value).toLocaleString('pt-PT', {
                            minimumFractionDigits: 0,
                        })
                    }}
                </div>
                <Badge :variant="STAGE_BADGE[deal.stage]">{{
                    STAGE_LABELS[deal.stage]
                }}</Badge>
            </div>
        </div>

        <!-- Stage Mover -->
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium">Move to stage:</span>
            <Select
                :model-value="deal.stage"
                :disabled="updatingStage"
                @update:model-value="updateStage"
            >
                <SelectTrigger class="w-48">
                    <SelectValue :placeholder="STAGE_LABELS[deal.stage]" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="s in STAGES" :key="s" :value="s">{{
                        STAGE_LABELS[s]
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <span class="text-sm text-muted-foreground"
                >{{ deal.probability }}% probability</span
            >
        </div>

        <!-- Details -->
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium">Contact: </span>
                <span v-if="!editingContact">
                    <a
                        v-if="deal.person"
                        :href="`/people/${deal.person.id}`"
                        class="text-primary hover:underline"
                        >{{ deal.person.name }}</a
                    >
                    <span v-else class="text-muted-foreground">—</span>
                    <button
                        class="ml-2 text-xs text-muted-foreground hover:text-foreground"
                        @click="
                            () => {
                                selectedPersonId = deal?.person
                                    ? String(deal.person.id)
                                    : '';
                                editingContact = true;
                            }
                        "
                    >
                        ✏ Change
                    </button>
                </span>
                <span v-else class="flex items-center gap-2">
                    <Select v-model="selectedPersonId">
                        <SelectTrigger class="h-7 w-48 text-xs">
                            <SelectValue placeholder="Select person" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="p in allPeople"
                                :key="p.id"
                                :value="String(p.id)"
                                >{{ p.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <Button size="sm" class="h-7 text-xs" @click="updateContact"
                        >Save</Button
                    >
                    <Button
                        size="sm"
                        variant="ghost"
                        class="h-7 text-xs"
                        @click="editingContact = false"
                        >Cancel</Button
                    >
                </span>
            </div>
            <div>
                <span class="font-medium">Owner: </span>
                <span>{{ deal.owner?.name ?? '—' }}</span>
            </div>
            <div>
                <span class="font-medium">Expected Close: </span>
                <span>{{ deal.expected_close_date ?? '—' }}</span>
            </div>
        </div>

        <!-- Notes -->
        <div v-if="deal.notes" class="rounded bg-muted/40 p-4 text-sm">
            <p class="mb-1 font-medium">Notes</p>
            <p class="whitespace-pre-line">{{ deal.notes }}</p>
        </div>

        <!-- Products -->
        <div>
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold">Products</h2>
                <Button
                    v-if="!editingProducts"
                    size="sm"
                    variant="outline"
                    @click="startEditProducts"
                    >{{
                        deal.products?.length ? '✏ Edit' : '+ Add Products'
                    }}</Button
                >
            </div>

            <!-- Read-only view -->
            <template v-if="!editingProducts">
                <table
                    v-if="deal.products?.length"
                    class="w-full rounded border text-sm"
                >
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-3 py-2 text-left">Product</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            <th class="px-3 py-2 text-right">Unit Price</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in deal.products"
                            :key="p.id"
                            class="border-t"
                        >
                            <td class="px-3 py-2">{{ p.name }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ p.quantity }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                €{{
                                    parseFloat(p.unit_price).toLocaleString(
                                        'pt-PT',
                                        { minimumFractionDigits: 2 },
                                    )
                                }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                €{{
                                    (
                                        p.quantity * parseFloat(p.unit_price)
                                    ).toLocaleString('pt-PT', {
                                        minimumFractionDigits: 2,
                                    })
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-sm text-muted-foreground">
                    No products added.
                </p>
            </template>

            <!-- Edit view -->
            <div v-else class="space-y-2">
                <div
                    v-for="(line, idx) in productLines"
                    :key="idx"
                    class="grid grid-cols-12 items-end gap-1"
                >
                    <div class="col-span-5">
                        <Label class="text-xs">Product</Label>
                        <Select
                            :model-value="line.product_id"
                            @update:model-value="(v) => onProductSelect(idx, v)"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select product" />
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
                        <Label class="text-xs">Qty</Label>
                        <Input
                            v-model.number="line.quantity"
                            type="number"
                            min="1"
                        />
                    </div>
                    <div class="col-span-3">
                        <Label class="text-xs">Unit € </Label>
                        <Input
                            v-model.number="line.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
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
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    @click="addProductLine"
                    >+ Add Line</Button
                >
                <p
                    v-if="productLines.length"
                    class="text-right text-xs font-medium"
                >
                    Total: €{{
                        productLinesTotal.toLocaleString('pt-PT', {
                            minimumFractionDigits: 2,
                        })
                    }}
                </p>
                <div class="flex gap-2">
                    <Button :disabled="syncingProducts" @click="saveProducts">{{
                        syncingProducts ? 'Saving…' : 'Save Products'
                    }}</Button>
                    <Button variant="ghost" @click="cancelEditProducts"
                        >Cancel</Button
                    >
                </div>
            </div>
        </div>

        <!-- Follow-up Automation -->
        <div class="space-y-3 rounded border p-4">
            <h2 class="font-semibold">Follow-up Automation</h2>
            <div v-if="followUp">
                <div class="flex items-center gap-2 text-sm">
                    <span class="font-medium">Status:</span>
                    <Badge
                        :variant="
                            followUp.status === 'active'
                                ? 'default'
                                : 'secondary'
                        "
                        >{{ followUp.status }}</Badge
                    >
                    <span
                        v-if="followUp.status === 'active'"
                        class="text-muted-foreground"
                    >
                        — {{ followUp.emails_sent }} sent · Next:
                        {{
                            followUp.next_send_at
                                ? formatDate(followUp.next_send_at)
                                : '—'
                        }}
                    </span>
                </div>
                <!-- Active follow-up controls -->
                <div v-if="followUp.status === 'active'" class="mt-3 space-y-3">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Override Template (optional)</label
                        >
                        <Select
                            :model-value="selectedTemplateId?.toString() ?? ''"
                            @update:model-value="
                                (v) =>
                                    (selectedTemplateId = v
                                        ? parseInt(v)
                                        : null)
                            "
                        >
                            <SelectTrigger
                                ><SelectValue
                                    :placeholder="
                                        followUp.email_template?.name ??
                                        'Keep current template'
                                    "
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value=""
                                    >Keep current template</SelectItem
                                >
                                <SelectItem
                                    v-for="t in emailTemplates"
                                    :key="t.id"
                                    :value="t.id.toString()"
                                    >{{ t.name }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="customEmailBody">
                        <label
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Email Body (editable)</label
                        >
                        <Textarea
                            v-model="customEmailBody"
                            class="resize-none font-mono text-xs"
                            :rows="5"
                        />
                    </div>
                    <p v-if="sendNowSuccess" class="text-xs text-green-600">
                        {{ sendNowSuccess }}
                    </p>
                    <p v-if="sendNowError" class="text-xs text-destructive">
                        {{ sendNowError }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            :disabled="sendingNow"
                            @click="sendNow"
                        >
                            {{ sendingNow ? 'Sending…' : '⚡ Send Now' }}
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            :disabled="cancellingFollowUp"
                            @click="cancelFollowUp"
                        >
                            {{
                                cancellingFollowUp
                                    ? 'Cancelling…'
                                    : 'Cancel Follow-up'
                            }}
                        </Button>
                    </div>
                </div>
                <div
                    v-if="followUp.email_template"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    Template: {{ followUp.email_template.name }}
                </div>
            </div>
            <!-- Start / re-start follow-up when there's no active one -->
            <div
                v-if="!followUp || followUp.status !== 'active'"
                class="flex flex-col gap-3"
            >
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label
                            class="mb-1 block text-xs font-medium text-muted-foreground"
                            >Email Template</label
                        >
                        <Select
                            :model-value="selectedTemplateId?.toString() ?? ''"
                            @update:model-value="
                                (v) =>
                                    (selectedTemplateId = v
                                        ? parseInt(v)
                                        : null)
                            "
                        >
                            <SelectTrigger
                                ><SelectValue placeholder="Select template…"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="t in emailTemplates"
                                    :key="t.id"
                                    :value="t.id.toString()"
                                    >{{ t.name }}</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <Button
                        size="sm"
                        :disabled="!selectedTemplateId || startingFollowUp"
                        @click="startFollowUp"
                    >
                        {{ startingFollowUp ? 'Starting…' : 'Start Follow-up' }}
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="!selectedTemplateId || sendingNow"
                        @click="sendNow"
                    >
                        {{ sendingNow ? 'Sending…' : '⚡ Send Now' }}
                    </Button>
                </div>
                <div v-if="customEmailBody" class="space-y-1">
                    <label
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Email Body (editable)</label
                    >
                    <Textarea
                        v-model="customEmailBody"
                        class="resize-none font-mono text-xs"
                        :rows="5"
                    />
                </div>
                <p v-if="sendNowSuccess" class="text-xs text-green-600">
                    {{ sendNowSuccess }}
                </p>
                <p v-if="sendNowError" class="text-xs text-destructive">
                    {{ sendNowError }}
                </p>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div>
            <h2 class="mb-3 font-semibold">Activity Timeline</h2>

            <!-- Icon Legend -->
            <details class="mb-3 rounded border bg-muted/20 p-3 text-xs">
                <summary
                    class="cursor-pointer font-medium text-muted-foreground"
                >
                    Icon Legend
                </summary>
                <div class="mt-2 grid grid-cols-2 gap-1 sm:grid-cols-3">
                    <span>📝 <strong>Note</strong> — internal note added</span>
                    <span>📞 <strong>Call</strong> — phone call logged</span>
                    <span
                        >✉️ <strong>Email</strong> — email sent or
                        received</span
                    >
                    <span
                        >🤝 <strong>Meeting</strong> — meeting took place</span
                    >
                    <span>✅ <strong>Task</strong> — task completed</span>
                    <span
                        >🔄 <strong>Stage change</strong> — deal moved to new
                        stage</span
                    >
                    <span>📌 <strong>Other</strong> — general activity</span>
                </div>
            </details>

            <!-- Quick Composer -->
            <div class="mb-4 space-y-3 rounded border bg-muted/20 p-4">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="t in ACTIVITY_TYPES"
                        :key="t"
                        :title="ACTIVITY_TYPE_DESCRIPTIONS[t]"
                        :class="[
                            'rounded-full border px-3 py-1 text-xs',
                            activityType === t
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted',
                        ]"
                        @click="activityType = t"
                    >
                        {{ ACTIVITY_TYPE_ICONS[t] }} {{ t }}
                    </button>
                </div>
                <Textarea
                    v-model="activityDescription"
                    placeholder="Add a note, log a call, meeting…"
                    class="resize-none"
                    rows="3"
                />
                <Button
                    :disabled="postingActivity || !activityDescription.trim()"
                    size="sm"
                    @click="postActivity"
                >
                    {{ postingActivity ? 'Saving…' : 'Log Activity' }}
                </Button>
            </div>

            <!-- Timeline entries -->
            <div v-if="timelineLoading" class="text-sm text-muted-foreground">
                Loading timeline…
            </div>
            <div
                v-else-if="!timeline.length"
                class="text-sm text-muted-foreground"
            >
                No activity yet.
            </div>
            <ol v-else class="relative ml-3 space-y-4 border-l border-muted">
                <li v-for="entry in timeline" :key="entry.id" class="ml-4">
                    <span
                        class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full border bg-background text-xs"
                    >
                        {{ ACTIVITY_TYPE_ICONS[entry.type] ?? '📌' }}
                    </span>
                    <div
                        class="rounded border bg-background p-3 text-sm shadow-sm"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span class="font-medium">{{
                                entry.user?.name ?? 'System'
                            }}</span>
                            <span class="text-xs text-muted-foreground">{{
                                formatDate(entry.created_at)
                            }}</span>
                        </div>
                        <p class="text-muted-foreground">
                            {{ entry.description }}
                        </p>
                        <div
                            v-if="
                                entry.metadata && entry.type === 'stage_change'
                            "
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ entry.metadata.from }} → {{ entry.metadata.to }}
                        </div>
                    </div>
                </li>
            </ol>

            <!-- Pagination controls -->
            <div
                v-if="timelineLastPage > 1"
                class="mt-4 flex items-center justify-between gap-2"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="timelinePage <= 1 || timelineLoading"
                    @click="fetchTimeline(timelinePage - 1)"
                >
                    ← Prev
                </Button>
                <span class="text-xs text-muted-foreground">
                    Page {{ timelinePage }} of {{ timelineLastPage }} ·
                    {{ timelineTotal }} entries
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="
                        timelinePage >= timelineLastPage || timelineLoading
                    "
                    @click="fetchTimeline(timelinePage + 1)"
                >
                    Next →
                </Button>
            </div>

            <!-- CSV Export -->
            <div class="mt-3 flex justify-end">
                <Button variant="outline" size="sm" @click="exportTimeline">
                    ⬇ Export CSV
                </Button>
            </div>
        </div>
    </div>
</template>
