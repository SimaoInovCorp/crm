<?php

namespace App\Http\Requests\Deal;

class StoreDealRequest extends DealRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_id'                  => ['required', 'integer', 'exists:entities,id'],
            'person_id'                  => ['nullable', 'integer', 'exists:people,id'],
            'owner_id'                   => ['nullable', 'integer', 'exists:users,id'],
            'title'                      => ['required', 'string', 'max:100'],
            'value'                      => ['nullable', 'numeric', 'min:0'],
            'stage'                      => ['nullable', 'string', 'in:lead,contact,proposal,negotiation,won,lost'],
            'probability'                => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date'        => ['nullable', 'date'],
            'notes'                      => ['nullable', 'string', 'max:1000'],
            'products'                   => ['nullable', 'array'],
            'products.*.product_id'      => ['required_with:products', 'integer', 'exists:products,id'],
            'products.*.quantity'        => ['required_with:products', 'integer', 'min:1'],
            'products.*.unit_price'      => ['required_with:products', 'numeric', 'min:0'],
        ];
    }
}
