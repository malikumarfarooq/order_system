<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'amount'          => 'required|numeric|min:0.01',
            'idempotency_key' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'           => 'Order title is required',
            'amount.required'          => 'Order amount is required',
            'amount.min'               => 'Amount must be greater than 0',
            'idempotency_key.required' => 'Idempotency key is required to prevent duplicate orders',
        ];
    }
}
