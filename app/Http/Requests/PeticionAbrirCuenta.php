<?php

namespace App\Http\Requests;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\DefinicionTipoCuenta;
use App\Domain\Customer\Cliente;
use App\Domain\Customer\RepositorioClientes;
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
            'familia' => ['nullable', 'string', Rule::in(['personal', 'empresarial'])],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_without:doc_number'],
            'name' => ['nullable', 'string', 'max:255', 'required_without:customer_id'],
            'doc_type' => ['nullable', 'string', Rule::in(['DNI', 'CC', 'RIF']), 'required_without:customer_id'],
            'doc_number' => ['nullable', 'string', 'max:100', 'required_without:customer_id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->filled('customer_id')) {
            return;
        }

        $repositorio = app(RepositorioClientes::class);
        $cliente = $repositorio->buscarPorDocumento($this->string('doc_type')->toString(), $this->string('doc_number')->toString());

        if ($cliente === null) {
            $cliente = new Cliente(
                nombre: $this->string('name')->toString(),
                tipoDocumento: $this->string('doc_type')->toString(),
                numeroDocumento: $this->string('doc_number')->toString(),
                email: $this->input('email'),
                telefono: $this->input('phone'),
            );
            $repositorio->guardar($cliente);
        }

        $this->merge(['customer_id' => $cliente->id()]);
    }

    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated();

        if (! array_key_exists('customer_id', $validated) && $this->filled('customer_id')) {
            $validated['customer_id'] = (int) $this->input('customer_id');
        }

        $validated['familia'] ??= 'personal';

        return data_get($validated, $key, $default);
    }
}
