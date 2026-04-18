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
import type { Person } from '@/types';
import { useEmailModal } from '@/composables/useEmailModal';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'People', href: '/people' },
            { title: 'Detail', href: '#' },
        ],
    },
});

const personId = window.location.pathname.split('/').pop();

const person = ref<Person | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const personDeals = ref<
    Array<{
        id: number;
        title: string;
        stage: string;
        value: string;
        expected_close_date: string | null;
        entity: { id: number; name: string } | null;
    }>
>([]);

const personEvents = ref<
    Array<{
        id: number;
        title: string;
        start_at: string;
        end_at: string;
        location: string | null;
    }>
>([]);

// ─── Email ────────────────────────────────────────────────────────────────────
const {
    showEmailModal,
    emailForm,
    sendingEmail,
    emailSuccess,
    emailError,
    openEmailModal,
    sendEmail,
} = useEmailModal(
    () => `/api/people/${personId}/email`,
    () => person.value?.email ?? '',
);

async function fetchPerson() {
    loading.value = true;

    try {
        const [personRes, dealsRes, eventsRes] = await Promise.all([
            axios.get<{ data: Person }>(`/api/people/${personId}`),
            axios.get('/api/deals', {
                params: { person_id: personId, per_page: 100 },
            }),
            axios.get('/api/calendar-events', { params: { per_page: 100 } }),
        ]);
        person.value = personRes.data.data;
        personDeals.value = dealsRes.data.data;
        // Filter events where this person is an attendee (person_id on event)
        personEvents.value = eventsRes.data.data.filter(
            (e: { person_id?: number }) =>
                e.person_id === parseInt(personId ?? '0'),
        );
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to load person.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetchPerson());
</script>

<template>
    <Head :title="person?.name ?? 'Person'" />

    <div
        v-if="loading"
        class="flex items-center justify-center p-12 text-muted-foreground"
    >
        Loading…
    </div>
    <p v-else-if="error" class="p-4 text-destructive">{{ error }}</p>

    <div v-else-if="person" class="flex flex-col gap-6 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ person.name }}</h1>
                <p v-if="person.position" class="text-sm text-muted-foreground">
                    {{ person.position }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button v-if="person.email" @click="openEmailModal"
                    >✉ Send Email</Button
                >
                <Button variant="outline" as="a" href="/people"
                    >Back to People</Button
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
                        <dd>{{ person.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>{{ person.phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Entity</dt>
                        <dd>
                            <a
                                v-if="person.entity"
                                :href="`/entities/${person.entity_id}`"
                                class="text-primary hover:underline"
                            >
                                {{ person.entity.name }}
                            </a>
                            <span v-else>—</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Position</dt>
                        <dd>{{ person.position ?? '—' }}</dd>
                    </div>
                    <div v-if="person.notes" class="col-span-2">
                        <dt class="text-muted-foreground">Notes</dt>
                        <dd class="whitespace-pre-line">{{ person.notes }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <!-- Deals History -->
        <Card v-if="personDeals.length">
            <CardHeader>
                <CardTitle>Deals ({{ personDeals.length }})</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="deal in personDeals"
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
                                v-if="deal.entity"
                                class="text-xs text-muted-foreground"
                            >
                                {{ deal.entity.name }}
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
                                >{{ deal.stage }}</Badge
                            >
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

        <!-- Events History -->
        <Card v-if="personEvents.length">
            <CardHeader>
                <CardTitle>Events ({{ personEvents.length }})</CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="divide-y text-sm">
                    <li
                        v-for="event in personEvents"
                        :key="event.id"
                        class="py-2"
                    >
                        <p class="font-medium">{{ event.title }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                new Date(event.start_at).toLocaleString(
                                    'pt-PT',
                                    {
                                        dateStyle: 'medium',
                                        timeStyle: 'short',
                                    },
                                )
                            }}
                            <span v-if="event.location"
                                >· {{ event.location }}</span
                            >
                        </p>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>

    <!-- Send Email Modal -->
    <Dialog v-model:open="showEmailModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Send Email to {{ person?.name }}</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">To: {{ person?.email }}</p>
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
                        rows="6"
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
