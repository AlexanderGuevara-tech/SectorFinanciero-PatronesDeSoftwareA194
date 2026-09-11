<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeticionTransferirFondos extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'destination_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:source_account_id'],
            'currency' => ['required', 'string', Rule::in(['COP', 'USD'])],
            'amount' => ['required', 'regex:/^\d+\.\d{2}$/'],
            'request_key' => ['required', 'string', 'max:255'],
        ];
    }
}
