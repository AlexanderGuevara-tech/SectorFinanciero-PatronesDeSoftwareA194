<?php

namespace App\Application\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\RepositorioCuentas;

final class BloquearCuenta
{
    public function __construct(
        private RepositorioCuentas $repositorio,
    ) {}

    public function ejecutar(int $cuentaId, int $userId): Cuenta
    {
        $cuenta = $this->repositorio->porId($cuentaId);

        if ($cuenta === null || $cuenta->userId() !== $userId) {
            throw new \InvalidArgumentException('Account not found for this owner.');
        }

        $cuenta->bloquear();
        $this->repositorio->guardar($cuenta);

        return $cuenta;
    }
}
