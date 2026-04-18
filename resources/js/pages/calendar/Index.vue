<script setup lang="ts">
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFormErrors } from '@/composables/useFormErrors';
import { useProductLines } from '@/composables/useProductLines';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Calendar', href: '/calendar' }] },
});

interface CalendarEventAttendee {
    id: number;
    attendee_type: string;
    attendee_id: number;
}

interface CalendarEventData {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    start_at: string;
    end_at: string;
    all_day: boolean;
    entity_id: number | null;
    person_id: number | null;
    deal_id: number | null;
    attendees: CalendarEventAttendee[];
}

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const saving = ref(false);
const { formErrors, extractErrors, clearErrors } = useFormErrors();
const serverError = ref<string | null>(null);
const sendingInvoice = ref(false);
const invoiceMessage = ref<{ type: 'success' | 'error'; text: string } | null>(
    null,
);

const entities = ref<{ id: number; name: string }[]>([]);
const people = ref<{ id: number; name: string; entity_id: number | null }[]>(
    [],
);
const deals = ref<{ id: number; title: string }[]>([]);
const allProducts = ref<{ id: number; name: string; price: string | null }[]>(
    [],
);

const {
    productLines: formProductLines,
    productLinesTotal: formProductsTotal,
    addProductLine: addFormProductLine,
    removeProductLine: removeFormProductLine,
    onProductSelect: onFormProductSelect,
} = useProductLines(allProducts);
const {
    productLines: editProductLines,
    productLinesTotal: editProductsTotal,
    addProductLine: addEditProductLine,
    removeProductLine: removeEditProductLine,
    onProductSelect: onEditProductSelect,
} = useProductLines(allProducts);

const form = ref({
    title: '',
    description: '',
    location: '',
    start_at: '',
    end_at: '',
    all_day: false,
    entity_id: '',
    person_id: '',
    deal_id: '',
    notify_person: false,
});

const editForm = ref({
    id: 0,
    title: '',
    description: '',
    location: '',
    start_at: '',
    end_at: '',
    all_day: false,
    entity_id: '',
    person_id: '',
    deal_id: '',
    notify_person: false,
});

onMounted(() => {
    Promise.all([
        axios.get('/api/entities', { params: { per_page: 200 } }).then((r) => {
            entities.value = r.data.data;
        }),
        axios.get('/api/people', { params: { per_page: 500 } }).then((r) => {
            people.value = r.data.data;
        }),
        axios.get('/api/deals', { params: { per_page: 200 } }).then((r) => {
            deals.value = r.data.data;
        }),
        axios.get('/api/products', { params: { per_page: 500 } }).then((r) => {
            allProducts.value = r.data.data;
        }),
    ]);
});

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    events: fetchEvents,
    dateClick: handleDateClick,
    eventClick: handleEventClick,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    height: 'auto',
}));

async function fetchEvents(
    info: { startStr: string; endStr: string },
    successCallback: (events: object[]) => void,
    failureCallback: (error: Error) => void,
) {
    try {
        const res = await axios.get('/api/calendar-events', {
            params: { start: info.startStr, end: info.endStr },
        });
        const events = res.data.data.map((e: CalendarEventData) => ({
            id: String(e.id),
            title: e.title,
            start: e.start_at,
            end: e.end_at,
            allDay: e.all_day,
            extendedProps: {
                description: e.description,
                location: e.location,
                entity_id: e.entity_id,
                person_id: e.person_id,
                deal_id: e.deal_id,
            },
        }));
        successCallback(events);
    } catch (err) {
        failureCallback(err as Error);
    }
}

function handleDateClick(info: { dateStr: string }) {
    form.value = {
        title: '',
        description: '',
        location: '',
        start_at: info.dateStr + 'T09:00',
        end_at: info.dateStr + 'T10:00',
        all_day: false,
        entity_id: '',
        person_id: '',
        deal_id: '',
        notify_person: false,
    };
    formProductLines.value = [];
    clearErrors();
    showCreateModal.value = true;
}

function handleEventClick(info: {
    event: {
        id: string;
        title: string;
        startStr: string;
        endStr: string;
        allDay: boolean;
        extendedProps: {
            description?: string;
            location?: string;
            entity_id?: number;
            person_id?: number;
            deal_id?: number;
        };
    };
}) {
    const e = info.event;
    editForm.value = {
        id: Number(e.id),
        title: e.title,
        description: e.extendedProps.description ?? '',
        location: e.extendedProps.location ?? '',
        start_at: e.startStr.slice(0, 16),
        end_at: e.endStr.slice(0, 16),
        all_day: e.allDay,
        entity_id: e.extendedProps.entity_id
            ? String(e.extendedProps.entity_id)
            : '',
        person_id: e.extendedProps.person_id
            ? String(e.extendedProps.person_id)
            : '',
        deal_id: e.extendedProps.deal_id ? String(e.extendedProps.deal_id) : '',
        notify_person: false,
    };
    editProductLines.value = [];
    clearErrors();
    invoiceMessage.value = null;
    showEditModal.value = true;
    // Fetch event products
    axios
        .get(`/api/calendar-events/${e.id}`)
        .then((r) => {
            const products = r.data.data?.products ?? [];
            editProductLines.value = products.map(
                (p: { id: number; quantity: number; unit_price: number }) => ({
                    product_id: String(p.id),
                    quantity: p.quantity,
                    unit_price: parseFloat(String(p.unit_price)),
                }),
            );
        })
        .catch(() => {});
}

async function handleEventDrop(info: {
    event: { id: string; startStr: string; endStr: string };
}) {
    await axios
        .put(`/api/calendar-events/${info.event.id}`, {
            start_at: info.event.startStr,
            end_at: info.event.endStr,
        })
        .catch(() => {
            // Revert handled by FullCalendar
        });
}

async function handleEventResize(info: {
    event: { id: string; startStr: string; endStr: string };
}) {
    await axios
        .put(`/api/calendar-events/${info.event.id}`, {
            start_at: info.event.startStr,
            end_at: info.event.endStr,
        })
        .catch(() => {});
}

async function createEvent() {
    saving.value = true;
    clearErrors();

    try {
        const validProducts = formProductLines.value.filter(
            (p) => p.product_id,
        );
        const payload = {
            ...form.value,
            entity_id: form.value.entity_id || null,
            person_id: form.value.person_id || null,
            deal_id: form.value.deal_id || null,
            products: validProducts.map((p) => ({
                product_id: parseInt(p.product_id),
                quantity: p.quantity,
                unit_price: p.unit_price,
            })),
        };
        await axios.post('/api/calendar-events', payload);
        showCreateModal.value = false;
        calendarRef.value?.getApi().refetchEvents();
    } catch (err: unknown) {
        if (!extractErrors(err)) {
            serverError.value = 'Failed to create event. Please try again.';
        }
    } finally {
        saving.value = false;
    }
}

async function updateEvent() {
    saving.value = true;
    clearErrors();

    try {
        const validProducts = editProductLines.value.filter(
            (p) => p.product_id,
        );
        await axios.put(`/api/calendar-events/${editForm.value.id}`, {
            ...editForm.value,
            products: validProducts.map((p) => ({
                product_id: parseInt(p.product_id),
                quantity: p.quantity,
                unit_price: p.unit_price,
            })),
        });
        showEditModal.value = false;
        calendarRef.value?.getApi().refetchEvents();
    } catch (err: unknown) {
        if (!extractErrors(err)) {
            serverError.value = 'Failed to update event. Please try again.';
        }
    } finally {
        saving.value = false;
    }
}

const showDeleteConfirm = ref(false);

function requestDeleteEvent() {
    showDeleteConfirm.value = true;
}

async function sendInvoice() {
    sendingInvoice.value = true;
    invoiceMessage.value = null;
    try {
        const { data } = await axios.post(
            `/api/calendar-events/${editForm.value.id}/send-invoice`,
        );
        invoiceMessage.value = { type: 'success', text: data.message };
    } catch (err: any) {
        invoiceMessage.value = {
            type: 'error',
            text: err.response?.data?.message ?? 'Failed to send invoice.',
        };
    } finally {
        sendingInvoice.value = false;
    }
}

async function deleteEvent() {
    showDeleteConfirm.value = false;
    await axios.delete(`/api/calendar-events/${editForm.value.id}`);
    showEditModal.value = false;
    calendarRef.value?.getApi().refetchEvents();
}
</script>

<template>
    <Head title="Calendar" />

    <div
        class="crm-table-container flex flex-col gap-4 p-4"
        style="background-color: #ece4d9"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Calendar</h1>
            <Button @click="showCreateModal = true">+ New Event</Button>
        </div>

        <!-- FullCalendar -->
        <div class="crm-table-container">
            <FullCalendar ref="calendarRef" :options="calendarOptions" />
        </div>
    </div>

    <!-- Create Modal -->
    <Dialog v-model:open="showCreateModal">
        <DialogContent class="max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>New Event</DialogTitle>
            </DialogHeader>
            <p v-if="serverError" class="text-sm text-destructive">
                {{ serverError }}
            </p>
            <div class="grid gap-4 py-2">
                <div>
                    <Label>Title *</Label>
                    <Input v-model="form.title" placeholder="Event title" />
                    <p v-if="formErrors.title" class="text-xs text-destructive">
                        {{ formErrors.title }}
                    </p>
                </div>
                <div>
                    <Label>Description</Label>
                    <Input
                        v-model="form.description"
                        placeholder="Optional description"
                    />
                </div>
                <div>
                    <Label>Location</Label>
                    <Input
                        v-model="form.location"
                        placeholder="Optional location"
                    />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <Label>Start *</Label>
                        <Input type="datetime-local" v-model="form.start_at" />
                        <p
                            v-if="formErrors.start_at"
                            class="text-xs text-destructive"
                        >
                            {{ formErrors.start_at }}
                        </p>
                    </div>
                    <div>
                        <Label>End *</Label>
                        <Input type="datetime-local" v-model="form.end_at" />
                        <p
                            v-if="formErrors.end_at"
                            class="text-xs text-destructive"
                        >
                            {{ formErrors.end_at }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="all_day"
                        v-model="form.all_day"
                    />
                    <Label for="all_day">All day</Label>
                </div>
                <p v-if="!form.all_day" class="text-xs text-muted-foreground">
                    Start and end date/time are required for non-all-day events.
                </p>
                <!-- Associations -->
                <div>
                    <Label>Entity</Label>
                    <Select v-model="form.entity_id">
                        <SelectTrigger
                            ><SelectValue
                                placeholder="Link to entity (optional)"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="e in entities"
                                :key="e.id"
                                :value="String(e.id)"
                                >{{ e.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label>Person</Label>
                    <Select v-model="form.person_id">
                        <SelectTrigger
                            ><SelectValue
                                placeholder="Link to person (optional)"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="p in people"
                                :key="p.id"
                                :value="String(p.id)"
                                >{{ p.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label>Deal</Label>
                    <Select v-model="form.deal_id">
                        <SelectTrigger
                            ><SelectValue placeholder="Link to deal (optional)"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="d in deals"
                                :key="d.id"
                                :value="String(d.id)"
                                >{{ d.title }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div v-if="form.person_id" class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="notify_person"
                        v-model="form.notify_person"
                    />
                    <Label for="notify_person"
                        >Send email notification to person</Label
                    >
                </div>
                <!-- Products -->
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <Label>Products</Label>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="addFormProductLine"
                        >
                            + Add Product
                        </Button>
                    </div>
                    <div
                        v-for="(line, idx) in formProductLines"
                        :key="idx"
                        class="mb-2 grid grid-cols-12 items-end gap-1"
                    >
                        <div class="col-span-5">
                            <Select
                                :model-value="line.product_id"
                                @update:model-value="
                                    (v) => onFormProductSelect(idx, v)
                                "
                            >
                                <SelectTrigger
                                    ><SelectValue placeholder="Product"
                                /></SelectTrigger>
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
                                @click="removeFormProductLine(idx)"
                                >✕</Button
                            >
                        </div>
                    </div>
                    <p
                        v-if="formProductLines.length"
                        class="text-right text-xs font-medium"
                    >
                        Total: €{{
                            formProductsTotal.toLocaleString('pt-PT', {
                                minimumFractionDigits: 2,
                            })
                        }}
                    </p>
                </div>
            </div>
            <DialogFooter>
                <Button
                    variant="outline"
                    @click="
                        showCreateModal = false;
                        serverError = null;
                    "
                    >Cancel</Button
                >
                <Button
                    :disabled="saving"
                    @click="
                        serverError = null;
                        createEvent();
                    "
                >
                    {{ saving ? 'Saving...' : 'Create Event' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit Modal -->
    <Dialog v-model:open="showEditModal">
        <DialogContent class="max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Edit Event</DialogTitle>
            </DialogHeader>
            <div class="grid gap-4 py-2">
                <div>
                    <Label>Title *</Label>
                    <Input v-model="editForm.title" />
                    <p v-if="formErrors.title" class="text-xs text-destructive">
                        {{ formErrors.title }}
                    </p>
                </div>
                <div>
                    <Label>Description</Label>
                    <Input v-model="editForm.description" />
                </div>
                <div>
                    <Label>Location</Label>
                    <Input v-model="editForm.location" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <Label>Start *</Label>
                        <Input
                            type="datetime-local"
                            v-model="editForm.start_at"
                        />
                    </div>
                    <div>
                        <Label>End *</Label>
                        <Input
                            type="datetime-local"
                            v-model="editForm.end_at"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="all_day_edit"
                        v-model="editForm.all_day"
                    />
                    <Label for="all_day_edit">All day</Label>
                </div>
                <p
                    v-if="!editForm.all_day"
                    class="text-xs text-muted-foreground"
                >
                    Start and end date/time are required for non-all-day events.
                </p>
                <!-- Associations -->
                <div>
                    <Label>Entity</Label>
                    <Select v-model="editForm.entity_id">
                        <SelectTrigger
                            ><SelectValue
                                placeholder="Link to entity (optional)"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="e in entities"
                                :key="e.id"
                                :value="String(e.id)"
                                >{{ e.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label>Person</Label>
                    <Select v-model="editForm.person_id">
                        <SelectTrigger
                            ><SelectValue
                                placeholder="Link to person (optional)"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="p in people"
                                :key="p.id"
                                :value="String(p.id)"
                                >{{ p.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label>Deal</Label>
                    <Select v-model="editForm.deal_id">
                        <SelectTrigger
                            ><SelectValue placeholder="Link to deal (optional)"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            <SelectItem
                                v-for="d in deals"
                                :key="d.id"
                                :value="String(d.id)"
                                >{{ d.title }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>
                <!-- Products -->
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <Label>Products</Label>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="addEditProductLine"
                        >
                            + Add Product
                        </Button>
                    </div>
                    <div
                        v-for="(line, idx) in editProductLines"
                        :key="idx"
                        class="mb-2 grid grid-cols-12 items-end gap-1"
                    >
                        <div class="col-span-5">
                            <Select
                                :model-value="line.product_id"
                                @update:model-value="
                                    (v) => onEditProductSelect(idx, v)
                                "
                            >
                                <SelectTrigger
                                    ><SelectValue placeholder="Product"
                                /></SelectTrigger>
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
                                @click="removeEditProductLine(idx)"
                                >✕</Button
                            >
                        </div>
                    </div>
                    <p
                        v-if="editProductLines.length"
                        class="text-right text-xs font-medium"
                    >
                        Total: €{{
                            editProductsTotal.toLocaleString('pt-PT', {
                                minimumFractionDigits: 2,
                            })
                        }}
                    </p>
                </div>
                <!-- Invoice -->
                <div
                    v-if="editForm.deal_id && editForm.person_id"
                    class="rounded border bg-muted/20 p-3"
                >
                    <p class="mb-2 text-xs font-medium text-muted-foreground">
                        📄 Invoice (requires deal + person)
                    </p>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="sendingInvoice"
                        @click="sendInvoice"
                    >
                        {{
                            sendingInvoice ? 'Sending…' : '📄 Send Invoice PDF'
                        }}
                    </Button>
                    <p
                        v-if="invoiceMessage"
                        class="mt-2 text-xs"
                        :class="
                            invoiceMessage.type === 'success'
                                ? 'text-green-600'
                                : 'text-destructive'
                        "
                    >
                        {{ invoiceMessage.text }}
                    </p>
                </div>
            </div>
            <DialogFooter class="flex justify-between">
                <Button variant="destructive" @click="requestDeleteEvent"
                    >Delete</Button
                >
                <div class="flex gap-2">
                    <Button variant="outline" @click="showEditModal = false"
                        >Cancel</Button
                    >
                    <Button :disabled="saving" @click="updateEvent">
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Modal -->
    <Dialog :open="showDeleteConfirm" @update:open="showDeleteConfirm = $event">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Delete Event</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <span class="font-semibold">{{ editForm.title }}</span
                >? This action cannot be undone.
            </p>
            <DialogFooter class="flex justify-end gap-2">
                <Button variant="outline" @click="showDeleteConfirm = false"
                    >Cancel</Button
                >
                <Button variant="destructive" @click="deleteEvent"
                    >Delete</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style>
/* FullCalendar dark mode is handled centrally in app.css */
/* No overrides needed here — all rules live in app.css with !important */
</style>
