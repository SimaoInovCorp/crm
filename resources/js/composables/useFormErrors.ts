import axios from 'axios';
import { ref } from 'vue';

/**
 * Manages form validation error state.
 * Handles 422 Unprocessable Entity responses from the API.
 */
export function useFormErrors() {
    const formErrors = ref<Record<string, string>>({});

    /**
     * Extracts field-level errors from a 422 response.
     * Returns true if the error was a 422 (handled), false otherwise.
     */
    function extractErrors(err: unknown): boolean {
        formErrors.value = {};

        if (axios.isAxiosError(err) && err.response?.status === 422) {
            const errs = err.response.data.errors as Record<string, string[]>;
            Object.keys(errs).forEach(
                (k) => (formErrors.value[k] = errs[k][0]),
            );
            return true;
        }

        return false;
    }

    function clearErrors(): void {
        formErrors.value = {};
    }

    return { formErrors, extractErrors, clearErrors };
}
