<?php

namespace App\Domain\Customer;

interface RepositorioClientes
{
    public function guardar(Cliente $cliente): void;

    public function porId(int $id): ?Cliente;

    public function buscarPorDocumento(string $tipoDocumento, string $numeroDocumento): ?Cliente;

    /** @return list<Cliente> */
    public function todos(): array;
}
