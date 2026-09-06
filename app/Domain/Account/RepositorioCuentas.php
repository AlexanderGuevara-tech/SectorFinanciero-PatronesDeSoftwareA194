<?php

namespace App\Domain\Account;

interface RepositorioCuentas
{
    public function guardar(Cuenta $cuenta): void;

    public function porId(int $id): ?Cuenta;

    /** @return list<Cuenta> */
    public function porCliente(int $customerId): array;

    /** @return list<Cuenta> */
    public function todos(): array;

    public function porIdYCliente(int $id, int $customerId): ?Cuenta;
}
