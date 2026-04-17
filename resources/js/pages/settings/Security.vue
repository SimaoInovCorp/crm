<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

type Props = {
    canManageTwoFactor: boolean;
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});

const tfEnabled = ref(props.twoFactorEnabled ?? false);
const showQr = ref(false);
const qrSvg = ref('');
const secretKey = ref('');
const recoveryCodes = ref<string[]>([]);
const tfConfirmCode = ref('');
const tfLoading = ref(false);
const showRecoveryCodes = ref(false);

async function enableTwoFactor() {
    tfLoading.value = true;

    try {
        await axios.post('/user/two-factor-authentication');
        const [qrRes, keyRes] = await Promise.all([
            axios.get('/user/two-factor-qr-code'),
            axios.get('/user/two-factor-secret-key'),
        ]);
        qrSvg.value = qrRes.data.svg;
        secretKey.value = keyRes.data.secretKey;
        showQr.value = true;
        window.location.reload();
    } finally {
        tfLoading.value = false;
    }
}

async function confirmTwoFactor() {
    tfLoading.value = true;

    try {
        await axios.post('/user/confirmed-two-factor-authentication', {
            code: tfConfirmCode.value,
        });
        showQr.value = false;
        tfEnabled.value = true;
    } finally {
        tfLoading.value = false;
    }
}

async function disableTwoFactor() {
    if (!confirm('Disable two-factor authentication?')) {
return;
}

    tfLoading.value = true;

    try {
        await axios.delete('/user/two-factor-authentication');
        tfEnabled.value = false;
        showQr.value = false;
    } finally {
        tfLoading.value = false;
    }
}

async function loadRecoveryCodes() {
    const { data } = await axios.get('/user/two-factor-recovery-codes');
    recoveryCodes.value = data;
    showRecoveryCodes.value = true;
}

async function regenerateRecoveryCodes() {
    await axios.post('/user/two-factor-recovery-codes');
    await loadRecoveryCodes();
}
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <!-- Password -->
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a strong password"
        />

        <Form
            v-bind="SecurityController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing, recentlySuccessful }"
        >
            <div class="grid gap-2">
                <Label for="current_password">Current password</Label>
                <Input
                    id="current_password"
                    type="password"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />
                <InputError :message="errors.current_password" />
            </div>

            <div class="grid gap-2">
                <Label for="password">New password</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save</Button>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-show="recentlySuccessful"
                        class="text-sm text-neutral-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </Form>

        <!-- Two-Factor Authentication -->
        <template v-if="canManageTwoFactor">
            <hr class="border-border" />

            <Heading
                variant="small"
                title="Two-factor authentication"
                description="Add additional security to your account using two-factor authentication"
            />

            <div v-if="!tfEnabled && !showQr" class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Two-factor authentication is currently disabled.
                </p>
                <Button @click="enableTwoFactor" :disabled="tfLoading"
                    >Enable</Button
                >
            </div>

            <div v-if="showQr" class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Scan this QR code with your authenticator app. Then enter
                    the code below to confirm.
                </p>
                <div v-html="qrSvg" class="w-40" />
                <p class="text-xs text-muted-foreground">
                    Secret key: <code>{{ secretKey }}</code>
                </p>
                <div class="flex items-center gap-2">
                    <Input
                        v-model="tfConfirmCode"
                        placeholder="6-digit code"
                        class="w-40"
                    />
                    <Button @click="confirmTwoFactor" :disabled="tfLoading"
                        >Confirm</Button
                    >
                </div>
            </div>

            <div v-if="tfEnabled && !showQr" class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Two-factor authentication is <strong>enabled</strong>.
                </p>
                <div class="flex gap-2">
                    <Button variant="outline" @click="loadRecoveryCodes"
                        >View recovery codes</Button
                    >
                    <Button
                        variant="destructive"
                        @click="disableTwoFactor"
                        :disabled="tfLoading"
                        >Disable</Button
                    >
                </div>

                <div v-if="showRecoveryCodes" class="space-y-2">
                    <p class="text-sm font-medium">Recovery codes</p>
                    <ul class="space-y-1 font-mono text-xs">
                        <li v-for="code in recoveryCodes" :key="code">
                            {{ code }}
                        </li>
                    </ul>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="regenerateRecoveryCodes"
                        >Regenerate</Button
                    >
                </div>
            </div>
        </template>
    </div>
</template>
