<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

interface Template {
    id: number;
    name: string;
    subject: string;
    body: string;
    type: string;
}

const templates = ref<Template[]>([]);
const loading = ref(true);
const showForm = ref(false);
const editing = ref<Template | null>(null);
const saving = ref(false);
const form = ref({ name: '', subject: '', body: '', type: 'general' });

const TYPES = ['general', 'follow_up', 'proposal'];

async function fetchTemplates() {
    const { data } = await axios.get('/api/email-templates');
    templates.value = data.data;
    loading.value = false;
}

function openCreate() {
    editing.value = null;
    form.value = { name: '', subject: '', body: '', type: 'general' };
    showForm.value = true;
}
function openEdit(t: Template) {
    editing.value = t;
    form.value = {
        name: t.name,
        subject: t.subject,
        body: t.body,
        type: t.type,
    };
    showForm.value = true;
}

async function save() {
    saving.value = true;

    try {
        if (editing.value) {
            await axios.put(
                `/api/email-templates/${editing.value.id}`,
                form.value,
            );
        } else {
            await axios.post('/api/email-templates', form.value);
        }

        showForm.value = false;
        await fetchTemplates();
    } finally {
        saving.value = false;
    }
}

const showDeleteModal = ref(false);
const templateToDelete = ref<Template | null>(null);

function confirmRemove(t: Template) {
    templateToDelete.value = t;
    showDeleteModal.value = true;
}

async function remove() {
    if (!templateToDelete.value) return;
    await axios.delete(`/api/email-templates/${templateToDelete.value.id}`);
    showDeleteModal.value = false;
    templateToDelete.value = null;
    await fetchTemplates();
}

onMounted(fetchTemplates);
</script>

<template>
    <Head title="Email Templates" />
    <div class="max-w-3xl space-y-4 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Email Templates</h1>
            <Button size="sm" @click="openCreate">+ New Template</Button>
        </div>

        <div v-if="loading" class="text-sm text-muted-foreground">Loading…</div>

        <div
            v-else-if="!templates.length"
            class="text-sm text-muted-foreground"
        >
            No email templates yet.
        </div>

        <div v-else class="space-y-2">
            <div
                v-for="t in templates"
                :key="t.id"
                class="card-depth flex items-center justify-between rounded-lg border p-3"
            >
                <div>
                    <span class="font-medium">{{ t.name }}</span>
                    <span
                        class="ml-2 rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                        >{{ t.type }}</span
                    >
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ t.subject }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" variant="outline" @click="openEdit(t)"
                        >Edit</Button
                    >
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="confirmRemove(t)"
                        >Delete</Button
                    >
                </div>
            </div>
        </div>

        <!-- Form -->
        <div v-if="showForm" class="space-y-3 rounded border bg-muted/10 p-4">
            <h2 class="font-semibold">
                {{ editing ? 'Edit Template' : 'New Template' }}
            </h2>
            <Input v-model="form.name" placeholder="Template name" />
            <Select
                :model-value="form.type"
                @update:model-value="
                    (v) => (form.type = String(v ?? 'general'))
                "
            >
                <SelectTrigger
                    ><SelectValue placeholder="Type"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="type in TYPES"
                        :key="type"
                        :value="type"
                        >{{ type }}</SelectItem
                    >
                </SelectContent>
            </Select>
            <Input v-model="form.subject" placeholder="Email subject" />
            <Textarea
                v-model="form.body"
                placeholder="HTML body (use {{contact_name}}, {{deal_title}}, etc.)"
                rows="8"
                class="font-mono text-xs"
            />
            <div class="rounded bg-muted/40 p-3 text-xs text-muted-foreground">
                <span class="font-semibold">Available variables:</span>
                <code class="mx-1 rounded bg-muted px-1"
                    >&#123;&#123;contact_name&#125;&#125;</code
                >
                — contact's full name,
                <code class="mx-1 rounded bg-muted px-1"
                    >&#123;&#123;name&#125;&#125;</code
                >
                — same as contact_name,
                <code class="mx-1 rounded bg-muted px-1"
                    >&#123;&#123;company&#125;&#125;</code
                >
                — linked entity/company name,
                <code class="mx-1 rounded bg-muted px-1"
                    >&#123;&#123;deal_title&#125;&#125;</code
                >
                — deal title,
                <code class="mx-1 rounded bg-muted px-1"
                    >&#123;&#123;stage&#125;&#125;</code
                >
                — current deal stage. HTML is supported in the body.
            </div>
            <div class="flex gap-2">
                <Button size="sm" :disabled="saving" @click="save">{{
                    saving ? 'Saving…' : 'Save'
                }}</Button>
                <Button size="sm" variant="outline" @click="showForm = false"
                    >Cancel</Button
                >
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Delete Template</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <span class="font-semibold">{{ templateToDelete?.name }}</span
                >? This action cannot be undone.
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
