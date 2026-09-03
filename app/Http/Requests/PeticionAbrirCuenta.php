<?php

namespace App\Http\Requests;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\DefinicionTipoCuenta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeticionAbrirCuenta extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $identificadores = array_map(
            fn (DefinicionTipoCuenta $definicion): string => $definicion->identificador,
            app(CatalogoTiposCuenta::class)->listar(),
        );

        return [
            'tipo' => ['required', 'string', Rule::in($identificadores)],
        ];
    }
}
