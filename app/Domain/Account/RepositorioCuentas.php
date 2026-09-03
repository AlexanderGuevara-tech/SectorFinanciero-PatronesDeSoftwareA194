<?php

namespace App\Domain\Account;

interface RepositorioCuentas
{
    public function guardar(Cuenta $cuenta): void;

    public function porId(int $id): ?Cuenta;

    /** @return list<Cuenta> */
    public function porUsuario(int $userId): array;

    /** @return list<Cuenta> */
    public function todos(): array;

    public function porIdYPropietario(int $id, int $userId): ?Cuenta;
}
