<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Customer\Cliente as ClienteDominio;
use App\Domain\Customer\RepositorioClientes;

final class RepositorioClientesEloquent implements RepositorioClientes
{
    public function guardar(ClienteDominio $cliente): void
    {
        $modelo = $cliente->id() === null
            ? new Cliente
            : Cliente::query()->findOrFail($cliente->id());

        $modelo->name = $cliente->nombre();
        $modelo->doc_type = $cliente->tipoDocumento();
        $modelo->doc_number = $cliente->numeroDocumento();
        $modelo->email = $cliente->email();
        $modelo->phone = $cliente->telefono();
        $modelo->save();

        if ($cliente->id() === null) {
            $cliente->asignarId($modelo->id);
        }
    }

    public function porId(int $id): ?ClienteDominio
    {
        $modelo = Cliente::query()->find($id);

        return $modelo === null ? null : $this->mapear($modelo);
    }

    public function buscarPorDocumento(string $tipoDocumento, string $numeroDocumento): ?ClienteDominio
    {
        $modelo = Cliente::query()
            ->where('doc_type', $tipoDocumento)
            ->where('doc_number', $numeroDocumento)
            ->first();

        return $modelo === null ? null : $this->mapear($modelo);
    }

    public function todos(): array
    {
        return Cliente::query()->get()->map(fn (Cliente $cliente): ClienteDominio => $this->mapear($cliente))->all();
    }

    private function mapear(Cliente $modelo): ClienteDominio
    {
        return new ClienteDominio(
            nombre: $modelo->name,
            tipoDocumento: $modelo->doc_type,
            numeroDocumento: $modelo->doc_number,
            email: $modelo->email,
            telefono: $modelo->phone,
            id: (int) $modelo->id,
        );
    }
}
