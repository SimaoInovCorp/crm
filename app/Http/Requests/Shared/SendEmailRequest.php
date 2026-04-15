<?php

namespace App\Http\Requests\Shared;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:120'],
            'body'    => ['required', 'string', 'max:3000'],
        ];
    }
}
