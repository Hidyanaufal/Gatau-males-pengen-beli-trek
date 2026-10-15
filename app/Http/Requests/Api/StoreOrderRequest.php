<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreOrderRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'fulfillment_type' => ['required', Rule::in(['drop_off', 'pickup_delivery'])],
            'address' => ['required_if:fulfillment_type,pickup_delivery', 'nullable', 'string', 'max:500'],
            'scheduled_at' => ['required_if:fulfillment_type,pickup_delivery', 'nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.service_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1', 'max:999'],
        ];
    }
}
