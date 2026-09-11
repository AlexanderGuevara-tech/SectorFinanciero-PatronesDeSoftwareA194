<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PeticionReversarTransferencia extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
            'request_key' => ['required', 'string', 'max:255'],
        ];
    }
}
