<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url'       => ['sometimes', 'url', 'max:2048'],
            'events'    => ['sometimes', 'array', 'min:1'],
            'events.*'  => ['required', 'string', 'in:deal.won,lead.created,automation.fired'],
            'secret'    => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
