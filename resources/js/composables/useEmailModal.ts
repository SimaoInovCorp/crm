import axios from 'axios';
import { ref } from 'vue';

/**
 * Manages the send-email modal state and submission logic.
 * @param getApiUrl  Factory returning the POST endpoint for this entity.
 * @param getRecipient  Factory returning the recipient email for the success message.
 */
export function useEmailModal(
    getApiUrl: () => string,
    getRecipient: () => string,
) {
    const showEmailModal = ref(false);
    const emailForm = ref({ subject: '', body: '' });
    const sendingEmail = ref(false);
    const emailSuccess = ref<string | null>(null);
    const emailError = ref<string | null>(null);

    function openEmailModal(): void {
        emailForm.value = { subject: '', body: '' };
        emailSuccess.value = null;
        emailError.value = null;
        showEmailModal.value = true;
    }

    async function sendEmail(): Promise<void> {
        if (!emailForm.value.subject.trim() || !emailForm.value.body.trim()) {
            return;
        }

        sendingEmail.value = true;
        emailSuccess.value = null;
        emailError.value = null;

        try {
            await axios.post(getApiUrl(), {
                subject: emailForm.value.subject,
                body: emailForm.value.body,
            });
            emailSuccess.value = `Email sent to ${getRecipient()} successfully.`;
            emailForm.value = { subject: '', body: '' };
        } catch (e: unknown) {
            const msg = (e as any)?.response?.data?.message;
            emailError.value = msg ?? 'Failed to send email.';
        } finally {
            sendingEmail.value = false;
        }
    }

    return {
        showEmailModal,
        emailForm,
        sendingEmail,
        emailSuccess,
        emailError,
        openEmailModal,
        sendEmail,
    };
}
