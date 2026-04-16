<?php

namespace App\Http\Requests\CalendarEvent;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'            => ['sometimes', 'required', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:500'],
            'location'         => ['nullable', 'string', 'max:100'],
            'start_at'         => ['sometimes', 'required', 'date'],
            'end_at'           => ['sometimes', 'required', 'date', 'after_or_equal:start_at'],
            'all_day'          => ['boolean'],
            'entity_id'        => ['nullable', 'integer', 'exists:entities,id'],
            'person_id'        => ['nullable', 'integer', 'exists:people,id'],
            'deal_id'          => ['nullable', 'integer', 'exists:deals,id'],
            'notify_person'    => ['boolean'],
            'eventable_type'   => ['nullable', 'string', 'in:deal,entity,person'],
            'eventable_id'     => ['nullable', 'integer'],
            'attendees'        => ['nullable', 'array'],
            'attendees.*.type' => ['required_with:attendees', 'string', 'in:user,person'],
            'attendees.*.id'   => ['required_with:attendees', 'integer'],
            'products'                  => ['nullable', 'array'],
            'products.*.product_id'     => ['required_with:products', 'integer', 'exists:products,id'],
            'products.*.quantity'       => ['required_with:products', 'integer', 'min:1'],
            'products.*.unit_price'     => ['required_with:products', 'numeric', 'min:0'],
        ];
    }
}
