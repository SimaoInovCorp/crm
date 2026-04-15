<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url'       => ['required', 'url', 'max:2048'],
            'events'    => ['required', 'array', 'min:1'],
            'events.*'  => ['required', 'string', 'in:deal.won,lead.created,automation.fired'],
            'secret'    => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
