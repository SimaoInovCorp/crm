<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { Entity, Person } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Entities', href: '/entities' },
            { title: 'Detail', href: '#' },
        ],
    },
});

// Extract the entity ID from the URL
const entityId = window.location.pathname.split('/').pop();

const entity = ref<Entity | null>(null);
const people = ref<Person[]>([]);
const deals = ref<
    Array<{
        id: number;
        title: string;
        stage: string;
        value: string;
        expected_close_date: string | null;
        probability: number;
        person: { id: number; name: string } | null;
    }>
>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// ─── Email ────────────────────────────────────────────────────────────────────
const showEmailModal = ref(false);
const emailForm = ref({ subject: '', body: '' });
const sendingEmail = ref(false);
const emailSuccess = ref<string | null>(null);
const emailError = ref<string | null>(null);

async function fetchEntity() {
    loading.value = true;

    try {
        const { data } = await axios.get<{ data: Entity }>(
            `/api/entities/${entityId}`,
        );
        entity.value = data.data;

        const [peopleRes, dealsRes] = await Promise.all([
            axios.get<{ data: Person[] }>('/api/people', {
                params: { entity_id: entityId },
            }),
            axios.get('/api/deals', {
                params: { entity_id: entityId, per_page: 100 },
            }),
        ]);
        people.value = peopleRes.data.data;
        deals.value = dealsRes.data.data;
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to load entity.';
    } finally {
        loading.value = false;
    }
}

function openEmailModal() {
    emailForm.value = { subject: '', body: '' };
    emailSuccess.value = null;
    emailError.value = null;
    showEmailModal.value = true;
}

async function sendEmail() {
    if (!emailForm.value.subject.trim() || !emailForm.value.body.trim()) {
        return;
    }

    sendingEmail.value = true;
    emailSuccess.value = null;
    emailError.value = null;

    try {
        await axios.post(`/api/entities/${entityId}/email`, {
            subject: emailForm.value.subject,
            body: emailForm.value.body,
        });
        emailSuccess.value = `Email sent to ${entity.value?.email} successfully.`;
        emailForm.value = { subject: '', body: '' };
    } catch (e: any) {
        emailError.value = e.response?.data?.message ?? 'Failed to send email.';
    } finally {
        sendingEmail.value = false;
    }
}

onMounted(() => fetchEntity());
</script>

<template>
    <Head :title="entity?.name ?? 'Entity'" />

    <div
        v-if="loading"
        class="flex items-center justify-center p-12 text-muted-foreground"
    >
        Loading…
    </div>
    <p v-else-if="error" class="p-4 text-destructive">{{ error }}</p>

    <div v-else-if="entity" class="flex flex-col gap-6 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ entity.name }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ entity.vat ?? 'No VAT' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <Badge variant="outline">{{ entity.status }}</Badge>
                <Button v-if="entity.email" @click="openEmailModal"
                    >✉ Send Email</Button
                >
                <Button variant="outline" as="a" href="/entities"
                    >Back to Entities</Button
                >
            </div>
        </div>

        <!-- Details Card -->
        <Card>
            <CardHeader>
                <CardTitle>Contact Information</CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-muted-foreground">Email</dt>
                        <dd>{{ entity.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>{{ entity.phone ?? '—' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-muted-foreground">Address</dt>
                        <dd class="whitespace-pre-line">
                            {{ entity.address ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <!-- People Card -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>People ({{ people.length }})</CardTitle>
                <Button
                    size="sm"
                    as="a"
                    :href="`/people?entity_id=${entity.id}`"
                    >View All</Button
                >
            </CardHeader>
            <CardContent>
                <p
                    v-if="people.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No people linked to this entity.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="person in people"
                        :key="person.id"
                        class="flex items-center justify-between py-2"
                    >
                        <div>
                            <a
                                :href="`/people/${person.id}`"
                                class="font-medium text-primary hover:underline"
                            >
                                {{ person.name }}
                            </a>
                            <p class="text-xs text-muted-foreground">
                                {{ person.position ?? person.email ?? '—' }}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            person.phone ?? ''
                        }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <!-- Company History — Deals -->
        <Card v-if="deals.length">
            <CardHeader>
                <CardTitle
                    >Company History ({{ deals.length }} Deals)</CardTitle
                >
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="deal in deals"
                        :key="deal.id"
                        class="flex items-center justify-between gap-2 py-2"
                    >
                        <div class="min-w-0">
                            <a
                                :href="`/deals/${deal.id}`"
                                class="font-medium text-primary hover:underline"
                                >{{ deal.title }}</a
                            >
                            <p
                                v-if="deal.person"
                                class="text-xs text-muted-foreground"
                            >
                                Contact: {{ deal.person.name }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <Badge
                                :variant="
                                    deal.stage === 'won'
                                        ? 'default'
                                        : deal.stage === 'lost'
                                          ? 'destructive'
                                          : 'secondary'
                                "
                                class="mb-1 block"
                            >
                                {{ deal.stage }}
                            </Badge>
                            <span class="text-xs text-muted-foreground"
                                >€{{
                                    parseFloat(deal.value).toLocaleString(
                                        'pt-PT',
                                        { minimumFractionDigits: 0 },
                                    )
                                }}</span
                            >
                        </div>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>

    <!-- Send Email Modal -->
    <Dialog v-model:open="showEmailModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Send Email to {{ entity?.name }}</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">To: {{ entity?.email }}</p>
            <div class="space-y-4">
                <div>
                    <Label for="email-subject">Subject *</Label>
                    <Input
                        id="email-subject"
                        v-model="emailForm.subject"
                        placeholder="Email subject"
                    />
                </div>
                <div>
                    <Label for="email-body">Message *</Label>
                    <Textarea
                        id="email-body"
                        v-model="emailForm.body"
                        placeholder="Write your message…"
                        :rows="6"
                    />
                </div>
                <p
                    v-if="emailSuccess"
                    class="text-sm text-green-600 dark:text-green-400"
                >
                    {{ emailSuccess }}
                </p>
                <p v-if="emailError" class="text-sm text-destructive">
                    {{ emailError }}
                </p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showEmailModal = false"
                    >Cancel</Button
                >
                <Button
                    :disabled="
                        sendingEmail ||
                        !emailForm.subject.trim() ||
                        !emailForm.body.trim()
                    "
                    @click="sendEmail"
                    >{{ sendingEmail ? 'Sending…' : 'Send Email' }}</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
