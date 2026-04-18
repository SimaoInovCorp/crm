<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';

interface Rule {
    id: number;
    name: string;
    trigger: string;
    conditions: Condition[] | null;
    actions: Action[];
    is_active: boolean;
}

interface Condition {
    field: string;
    operator: string;
    value: string;
}

interface Action {
    type: string;
    message: string;
}

// ─── Trigger options (human-readable) ─────────────────────────────────────────
const TRIGGER_OPTIONS = [
    { value: 'deal_stage_changed', label: 'A deal changes stage' },
    { value: 'deal_created', label: 'A new deal is created' },
    { value: 'deal_idle', label: 'A deal has had no activity for too long' },
];

const FIELD_OPTIONS = [
    { value: 'stage', label: 'Deal Stage' },
    { value: 'value', label: 'Deal Value (€)' },
];

const OPERATOR_OPTIONS = [
    { value: '=', label: 'is equal to' },
    { value: '!=', label: 'is NOT equal to' },
    { value: '>', label: 'is greater than' },
    { value: '<', label: 'is less than' },
];

const STAGE_VALUES = [
    'lead',
    'qualified',
    'proposal',
    'negotiation',
    'won',
    'lost',
];

const ACTION_TYPE_OPTIONS = [
    { value: 'notify_owner', label: 'Send a notification to the deal owner' },
];

// ─── State ────────────────────────────────────────────────────────────────────
const rules = ref<Rule[]>([]);
const loading = ref(true);
const showForm = ref(false);
const editing = ref<Rule | null>(null);
const saving = ref(false);

const form = ref({
    name: '',
    trigger: 'deal_stage_changed',
    is_active: true,
});
const conditions = ref<Condition[]>([]);
const actions = ref<Action[]>([{ type: 'notify_owner', message: '' }]);

async function fetchRules() {
    const { data } = await axios.get('/api/automation-rules');
    rules.value = data.data;
    loading.value = false;
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', trigger: 'deal_stage_changed', is_active: true };
    conditions.value = [];
    actions.value = [{ type: 'notify_owner', message: '' }];
    showForm.value = true;
}

function openEdit(r: Rule) {
    editing.value = r;
    form.value = { name: r.name, trigger: r.trigger, is_active: r.is_active };
    conditions.value = (r.conditions ?? []).map((c) => ({ ...c }));
    actions.value = r.actions.map((a) => ({
        type: a.type,
        message: a.message ?? '',
    }));
    showForm.value = true;
}

function addCondition() {
    conditions.value.push({ field: 'stage', operator: '=', value: '' });
}

function removeCondition(idx: number) {
    conditions.value.splice(idx, 1);
}

function addAction() {
    actions.value.push({ type: 'notify_owner', message: '' });
}

function removeAction(idx: number) {
    actions.value.splice(idx, 1);
}

async function save() {
    if (!form.value.name.trim()) {
        toast.error('Please enter a rule name.');

        return;
    }

    if (!actions.value.length) {
        toast.error('Please add at least one action.');

        return;
    }

    saving.value = true;

    try {
        const payload = {
            ...form.value,
            conditions: conditions.value,
            actions: actions.value,
        };

        if (editing.value) {
            await axios.put(
                `/api/automation-rules/${editing.value.id}`,
                payload,
            );
        } else {
            await axios.post('/api/automation-rules', payload);
        }

        showForm.value = false;
        toast.success('Rule saved successfully!');
        await fetchRules();
    } catch {
        toast.error('Failed to save rule. Please try again.');
    } finally {
        saving.value = false;
    }
}

async function toggleActive(r: Rule) {
    await axios.put(`/api/automation-rules/${r.id}`, {
        is_active: !r.is_active,
    });
    r.is_active = !r.is_active;
}

const showDeleteModal = ref(false);
const ruleToDelete = ref<Rule | null>(null);

function confirmRemove(r: Rule) {
    ruleToDelete.value = r;
    showDeleteModal.value = true;
}

async function remove() {
    if (!ruleToDelete.value) {
return;
}

    await axios.delete(`/api/automation-rules/${ruleToDelete.value.id}`);
    showDeleteModal.value = false;
    ruleToDelete.value = null;
    toast.success('Rule deleted.');
    await fetchRules();
}

function triggerLabel(value: string): string {
    return TRIGGER_OPTIONS.find((t) => t.value === value)?.label ?? value;
}

onMounted(fetchRules);
</script>

<template>
    <Head title="Automation Rules" />
    <div class="max-w-4xl space-y-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Automation Rules</h1>
            <Button size="sm" @click="openCreate">+ New Rule</Button>
        </div>

        <!-- ── Plain-language info banner ──────────────────────────────── -->
        <div
            class="rounded-lg border border-purple-200 bg-purple-50 p-4 text-sm dark:border-purple-900 dark:bg-purple-950/40"
        >
            <p class="mb-2 font-semibold text-purple-800 dark:text-purple-300">
                ⚡ What are Automation Rules?
            </p>
            <p class="mb-3 text-purple-700 dark:text-purple-400">
                An <strong>Automation Rule</strong> is an instruction you set up
                once, and the system follows it automatically. Think of it as:
                <em>"Whenever X happens, if Y is true, then do Z"</em> — without
                anyone having to remember to do it manually.
            </p>
            <p class="mb-2 font-medium text-purple-800 dark:text-purple-300">
                Example — how to create a rule:
            </p>
            <ol
                class="list-decimal space-y-1 pl-5 text-purple-700 dark:text-purple-400"
            >
                <li>
                    Click <strong>+ New Rule</strong> and give it a name (e.g.
                    "Notify me when a deal is won").
                </li>
                <li>
                    Choose the <strong>trigger</strong>: what event should start
                    this rule? (e.g. "A deal changes stage").
                </li>
                <li>
                    Optionally add a <strong>condition</strong>: should it only
                    run sometimes? (e.g. only when stage becomes "won").
                </li>
                <li>
                    Choose the <strong>action</strong>: what should happen?
                    (e.g. "Send a notification to the deal owner").
                </li>
                <li>
                    Save — the rule will run automatically from now on every
                    time that situation occurs.
                </li>
            </ol>
        </div>

        <div v-if="loading" class="text-sm text-muted-foreground">Loading…</div>
        <div v-else-if="!rules.length" class="text-sm text-muted-foreground">
            No automation rules yet. Click <strong>+ New Rule</strong> to create
            one.
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="r in rules"
                :key="r.id"
                class="card-depth flex items-center justify-between rounded-lg border p-3"
            >
                <div>
                    <span class="font-medium">{{ r.name }}</span>
                    <span
                        class="ml-2 rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ triggerLabel(r.trigger) }}
                    </span>
                    <span
                        v-if="!r.is_active"
                        class="ml-2 text-xs text-destructive"
                        >(inactive)</span
                    >
                </div>
                <div class="flex gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="toggleActive(r)"
                    >
                        {{ r.is_active ? 'Disable' : 'Enable' }}
                    </Button>
                    <Button size="sm" variant="outline" @click="openEdit(r)"
                        >Edit</Button
                    >
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="confirmRemove(r)"
                        >Delete</Button
                    >
                </div>
            </div>
        </div>

        <!-- ── Rule Form ─────────────────────────────────────────────────── -->
        <div
            v-if="showForm"
            class="space-y-5 rounded-lg border bg-muted/10 p-5"
        >
            <h2 class="font-semibold">
                {{ editing ? 'Edit Rule' : 'New Automation Rule' }}
            </h2>

            <!-- Name -->
            <div class="space-y-1">
                <label class="text-sm font-medium">Rule Name</label>
                <Input
                    v-model="form.name"
                    placeholder="e.g. Notify when deal is won"
                />
            </div>

            <!-- Active toggle -->
            <div class="flex items-center gap-2">
                <Switch v-model:checked="form.is_active" />
                <span class="text-sm"
                    >Active (rule will run automatically)</span
                >
            </div>

            <!-- Trigger -->
            <div class="space-y-1">
                <label class="text-sm font-medium"
                    >Trigger — WHEN does this rule run?</label
                >
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="opt in TRIGGER_OPTIONS"
                        :key="opt.value"
                        type="button"
                        class="rounded-lg border px-3 py-2 text-sm transition-colors"
                        :class="
                            form.trigger === opt.value
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-background hover:bg-muted'
                        "
                        @click="form.trigger = opt.value"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- Conditions -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">
                        Conditions — IF these are true
                        <span class="ml-1 font-normal text-muted-foreground"
                            >(optional — leave empty to always run)</span
                        >
                    </label>
                    <Button size="sm" variant="outline" @click="addCondition"
                        >+ Add Condition</Button
                    >
                </div>
                <div
                    v-if="!conditions.length"
                    class="text-xs text-muted-foreground"
                >
                    No conditions — this rule will fire for every matching
                    trigger.
                </div>
                <div
                    v-for="(cond, idx) in conditions"
                    :key="idx"
                    class="flex flex-wrap items-center gap-2 rounded-lg border bg-background p-3"
                >
                    <select
                        v-model="cond.field"
                        class="rounded border bg-background px-2 py-1.5 text-sm"
                    >
                        <option
                            v-for="f in FIELD_OPTIONS"
                            :key="f.value"
                            :value="f.value"
                        >
                            {{ f.label }}
                        </option>
                    </select>
                    <select
                        v-model="cond.operator"
                        class="rounded border bg-background px-2 py-1.5 text-sm"
                    >
                        <option
                            v-for="op in OPERATOR_OPTIONS"
                            :key="op.value"
                            :value="op.value"
                        >
                            {{ op.label }}
                        </option>
                    </select>
                    <template v-if="cond.field === 'stage'">
                        <select
                            v-model="cond.value"
                            class="rounded border bg-background px-2 py-1.5 text-sm"
                        >
                            <option value="">Select stage…</option>
                            <option
                                v-for="s in STAGE_VALUES"
                                :key="s"
                                :value="s"
                            >
                                {{ s.charAt(0).toUpperCase() + s.slice(1) }}
                            </option>
                        </select>
                    </template>
                    <template v-else>
                        <Input
                            v-model="cond.value"
                            placeholder="Value"
                            class="w-32"
                        />
                    </template>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="removeCondition(idx)"
                        >✕</Button
                    >
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium"
                        >Actions — THEN do this</label
                    >
                    <Button size="sm" variant="outline" @click="addAction"
                        >+ Add Action</Button
                    >
                </div>
                <div
                    v-for="(action, idx) in actions"
                    :key="idx"
                    class="space-y-2 rounded-lg border bg-background p-3"
                >
                    <div class="flex items-center gap-2">
                        <select
                            v-model="action.type"
                            class="flex-1 rounded border bg-background px-2 py-1.5 text-sm"
                        >
                            <option
                                v-for="opt in ACTION_TYPE_OPTIONS"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="removeAction(idx)"
                            >✕</Button
                        >
                    </div>
                    <Input
                        v-model="action.message"
                        placeholder="Notification message, e.g. Deal won! Follow up with the client."
                    />
                </div>
                <p v-if="!actions.length" class="text-xs text-destructive">
                    At least one action is required.
                </p>
            </div>

            <div class="flex gap-2">
                <Button :disabled="saving" @click="save">{{
                    saving ? 'Saving…' : 'Save Rule'
                }}</Button>
                <Button variant="outline" @click="showForm = false"
                    >Cancel</Button
                >
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Delete Rule</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <span class="font-semibold">{{ ruleToDelete?.name }}</span
                >? This cannot be undone.
            </p>
            <DialogFooter class="flex justify-end gap-2">
                <Button variant="outline" @click="showDeleteModal = false"
                    >Cancel</Button
                >
                <Button variant="destructive" @click="remove">Delete</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
