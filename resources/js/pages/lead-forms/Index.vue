<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/components/ui/dialog';

interface FieldDef {
    label: string;
    type: string;
    required: boolean;
}
interface LeadForm {
    id: number;
    name: string;
    fields: FieldDef[];
    is_active: boolean;
    embed_token: string;
    submissions_count?: number;
}
interface Submission {
    id: number;
    data: Record<string, unknown>;
    ip: string | null;
    origin: string | null;
    processed: boolean;
    created_at: string;
}

const forms = ref<LeadForm[]>([]);
const loading = ref(true);
const showForm = ref(false);
const editing = ref<LeadForm | null>(null);
const saving = ref(false);
const form = ref({ name: '', fields: [] as FieldDef[], is_active: true });

const selectedForm = ref<LeadForm | null>(null);
const submissions = ref<Submission[]>([]);
const submissionsLoading = ref(false);

const FIELD_TYPES = ['text', 'email', 'phone', 'textarea', 'number'];

async function fetchForms() {
    const { data } = await axios.get('/api/lead-forms');
    forms.value = data.data;
    loading.value = false;
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', fields: [], is_active: true };
    showForm.value = true;
}

function openEdit(f: LeadForm) {
    editing.value = f;
    form.value = {
        name: f.name,
        fields: [...f.fields],
        is_active: f.is_active,
    };
    showForm.value = true;
}

function addField() {
    form.value.fields.push({ label: '', type: 'text', required: false });
}

function removeField(idx: number) {
    form.value.fields.splice(idx, 1);
}

async function save() {
    saving.value = true;

    try {
        if (editing.value) {
            await axios.put(`/api/lead-forms/${editing.value.id}`, form.value);
        } else {
            await axios.post('/api/lead-forms', form.value);
        }

        showForm.value = false;
        await fetchForms();
    } finally {
        saving.value = false;
    }
}

const showDeleteModal = ref(false);
const formToDelete = ref<LeadForm | null>(null);

function confirmRemove(f: LeadForm) {
    formToDelete.value = f;
    showDeleteModal.value = true;
}

async function remove() {
    if (!formToDelete.value) return;
    await axios.delete(`/api/lead-forms/${formToDelete.value.id}`);
    showDeleteModal.value = false;
    formToDelete.value = null;
    await fetchForms();
}

async function viewSubmissions(f: LeadForm) {
    selectedForm.value = f;
    submissionsLoading.value = true;
    const { data } = await axios.get(`/api/lead-forms/${f.id}/submissions`);
    submissions.value = data.data;
    submissionsLoading.value = false;
}

function embedCode(f: LeadForm): string {
    const url = `${window.location.origin}/api/public/forms/${f.embed_token}`;

    // Build a field example object from the form's own fields
    const exampleFields: Record<string, string> = {};
    if (f.fields.length) {
        f.fields.forEach((field) => {
            exampleFields[field.label] =
                field.type === 'email'
                    ? 'example@email.com'
                    : field.type === 'phone'
                      ? '+351 900 000 000'
                      : field.type === 'number'
                        ? '1'
                        : 'Your answer here';
        });
    } else {
        exampleFields['name'] = 'John Doe';
        exampleFields['email'] = 'john@example.com';
    }

    return `fetch('${url}', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(${JSON.stringify(exampleFields, null, 4).replace(/\n/g, '\n  ')})
}).then(r => r.json()).then(console.log);`;
}

// ─── Embed Dialog ────────────────────────────────────────────────────────────
const embedDialogOpen = ref(false);
const embedDialogForm = ref<LeadForm | null>(null);
const embedTextareaRef = ref<HTMLTextAreaElement | null>(null);

function openEmbedDialog(f: LeadForm) {
    embedDialogForm.value = f;
    embedDialogOpen.value = true;
    // Wait for next tick to focus/select if needed
}

async function copyEmbedCode() {
    if (embedTextareaRef.value) {
        embedTextareaRef.value.select();
        try {
            document.execCommand('copy');
            toast.success('Code copied to clipboard!');
            return;
        } catch {}
    }
    // fallback: try navigator.clipboard
    const code = embedDialogForm.value ? embedCode(embedDialogForm.value) : '';
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(code);
            toast.success('Code copied to clipboard!');
        } else {
            throw new Error();
        }
    } catch {
        toast.error(
            'Could not copy automatically. Please select and copy the code manually.',
        );
    }
}

onMounted(fetchForms);
</script>

<template>
    <Head title="Lead Forms" />
    <div class="max-w-4xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Lead Forms</h1>
            <Button size="sm" @click="openCreate">+ New Form</Button>
        </div>

        <!-- ── Plain-language info banner ─────────────────────────────── -->
        <div
            class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm dark:border-blue-900 dark:bg-blue-950/40"
        >
            <p class="mb-2 font-semibold text-blue-800 dark:text-blue-300">
                💡 What are Lead Forms?
            </p>
            <p class="mb-3 text-blue-700 dark:text-blue-400">
                A <strong>Lead Form</strong> is an online form that potential
                customers fill in on your website — for example a "Contact Us"
                or "Request a Quote" form. Every time someone submits it, their
                details arrive here automatically so your team can follow up.
            </p>
            <p class="font-medium text-blue-800 dark:text-blue-300">
                How to add it to your website:
            </p>
            <ol
                class="mt-1 list-decimal space-y-1 pl-5 text-blue-700 dark:text-blue-400"
            >
                <li>
                    Create a form below and define the fields you want to
                    collect (e.g. Name, Email, Message).
                </li>
                <li>
                    Click <strong>Get Embed Code</strong> on any form — you'll
                    get step-by-step setup instructions.
                </li>
                <li>
                    Share the instructions with whoever manages your website.
                    Done!
                </li>
            </ol>
        </div>

        <div v-if="loading" class="text-sm text-muted-foreground">Loading…</div>

        <div v-else-if="!forms.length" class="text-sm text-muted-foreground">
            No lead forms yet. Click <strong>+ New Form</strong> to create one.
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="f in forms"
                :key="f.id"
                class="card-depth space-y-2 rounded-lg border p-4"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-medium">{{ f.name }}</span>
                        <span
                            v-if="!f.is_active"
                            class="ml-2 rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                            >Inactive</span
                        >
                        <span class="ml-2 text-xs text-muted-foreground"
                            >{{ f.submissions_count ?? 0 }} submission(s)</span
                        >
                    </div>
                    <div class="flex gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            title="View all form submissions received through this form"
                            @click="viewSubmissions(f)"
                            >Submissions</Button
                        >
                        <Button
                            size="sm"
                            variant="outline"
                            title="Get step-by-step instructions to add this form to your website"
                            @click="openEmbedDialog(f)"
                            >Get Embed Code</Button
                        >
                        <Button size="sm" variant="outline" @click="openEdit(f)"
                            >Edit</Button
                        >
                        <Button
                            size="sm"
                            variant="destructive"
                            @click="confirmRemove(f)"
                            >Delete</Button
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Form -->
        <div
            v-if="showForm"
            class="space-y-4 rounded-lg border bg-muted/10 p-4"
        >
            <h2 class="font-semibold">
                {{ editing ? 'Edit Form' : 'New Lead Form' }}
            </h2>
            <div class="space-y-2">
                <label class="text-sm font-medium">Form Name</label>
                <Input
                    v-model="form.name"
                    placeholder="e.g. Contact Us, Request a Quote"
                />
            </div>
            <div class="flex items-center gap-2">
                <Switch v-model:checked="form.is_active" />
                <span class="text-sm">Active (accepting submissions)</span>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">Fields to collect</label>
                    <Button size="sm" variant="outline" @click="addField"
                        >+ Add Field</Button
                    >
                </div>
                <p class="text-xs text-muted-foreground">
                    Each field is a piece of information the visitor fills in
                    (e.g. their name, email or a message).
                </p>
                <div
                    v-for="(field, idx) in form.fields"
                    :key="idx"
                    class="flex items-center gap-2 rounded border bg-background p-2"
                >
                    <Input
                        v-model="field.label"
                        placeholder="Field label (e.g. Email)"
                        class="flex-1"
                    />
                    <select
                        v-model="field.type"
                        class="rounded border bg-background px-2 py-1 text-sm"
                    >
                        <option value="text">Short text</option>
                        <option value="email">Email address</option>
                        <option value="phone">Phone number</option>
                        <option value="textarea">Long text / message</option>
                        <option value="number">Number</option>
                    </select>
                    <label class="flex items-center gap-1 text-sm">
                        <input type="checkbox" v-model="field.required" />
                        Required
                    </label>
                    <Button size="sm" variant="ghost" @click="removeField(idx)"
                        >✕</Button
                    >
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <Button variant="outline" @click="showForm = false"
                    >Cancel</Button
                >
                <Button :disabled="saving" @click="save">{{
                    saving ? 'Saving…' : 'Save'
                }}</Button>
            </div>
        </div>

        <!-- Submissions Panel -->
        <div v-if="selectedForm" class="space-y-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">
                    Submissions: {{ selectedForm.name }}
                </h2>
                <Button size="sm" variant="ghost" @click="selectedForm = null"
                    >✕ Close</Button
                >
            </div>
            <div
                v-if="submissionsLoading"
                class="text-sm text-muted-foreground"
            >
                Loading…
            </div>
            <div
                v-else-if="!submissions.length"
                class="text-sm text-muted-foreground"
            >
                No submissions yet.
            </div>
            <div v-else class="space-y-2">
                <div
                    v-for="s in submissions"
                    :key="s.id"
                    class="space-y-1 rounded border p-3 text-sm"
                >
                    <div
                        class="flex justify-between text-xs text-muted-foreground"
                    >
                        <span>{{ s.created_at }}</span>
                        <span>{{ s.ip }}</span>
                        <span
                            :class="
                                s.processed
                                    ? 'text-green-600'
                                    : 'text-yellow-600'
                            "
                        >
                            {{ s.processed ? 'Processed' : 'Pending' }}
                        </span>
                    </div>
                    <pre class="overflow-x-auto rounded bg-muted p-2 text-xs">{{
                        JSON.stringify(s.data, null, 2)
                    }}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Embed Code Dialog ──────────────────────────────────────────── -->
    <Dialog :open="embedDialogOpen" @update:open="embedDialogOpen = $event">
        <DialogContent class="max-h-[500px] max-w-xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle
                    >Add "{{ embedDialogForm?.name }}" to your
                    website</DialogTitle
                >
                <DialogDescription>
                    Follow these three steps to start collecting leads from your
                    website.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 text-sm">
                <div
                    class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950/40"
                >
                    <p
                        class="mb-1 font-semibold text-green-800 dark:text-green-300"
                    >
                        Step 1 — Copy the code below
                    </p>
                    <p class="text-green-700 dark:text-green-400">
                        Click the <strong>Copy Code</strong> button to copy the
                        snippet to your clipboard.
                    </p>
                </div>

                <div class="relative">
                    <textarea
                        ref="embedTextareaRef"
                        readonly
                        rows="7"
                        class="w-full rounded-lg border bg-muted p-3 font-mono text-xs select-all"
                        :value="
                            embedDialogForm ? embedCode(embedDialogForm) : ''
                        "
                    />
                    <Button
                        size="sm"
                        class="absolute top-2 right-2"
                        @click="copyEmbedCode"
                        >Copy Code</Button
                    >
                </div>

                <div class="rounded-lg border p-3">
                    <p class="mb-1 font-semibold">
                        Step 2 — Send it to your web developer
                    </p>
                    <p class="text-muted-foreground">
                        Forward the copied code to whoever manages your website
                        and ask them to paste it just before the closing
                        <code class="rounded bg-muted px-1">&lt;/body&gt;</code>
                        tag on the page where you want the form.
                    </p>
                    <p class="mt-2 text-muted-foreground">
                        <strong>No developer?</strong> Most website builders
                        (WordPress, Wix, Webflow, Squarespace) have an "Embed"
                        or "Custom HTML" block — simply paste it there.
                    </p>
                </div>

                <div class="rounded-lg border p-3">
                    <p class="mb-1 font-semibold">Step 3 — You're done! 🎉</p>
                    <p class="text-muted-foreground">
                        Every time a visitor submits the form on your website,
                        their details will appear automatically in the
                        <strong>Submissions</strong> panel here in the CRM.
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        The field names in the code must match exactly the field
                        labels you defined in this form.
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="embedDialogOpen = false"
                    >Close</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Delete Lead Form</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <span class="font-semibold">{{ formToDelete?.name }}</span
                >? All submissions will also be removed. This cannot be undone.
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
