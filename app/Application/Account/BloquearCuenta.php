<?php

namespace App\Application\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\RepositorioCuentas;

final class BloquearCuenta
{
    public function __construct(
        private RepositorioCuentas $repositorio,
    ) {}

    public function ejecutar(int $cuentaId, int $customerId): Cuenta
    {
        $cuenta = $this->repositorio->porIdYCliente($cuentaId, $customerId);

        if ($cuenta === null) {
            throw new \InvalidArgumentException('Account not found for this customer.');
        }

        $cuenta->bloquear();
        $this->repositorio->guardar($cuenta);

        return $cuenta;
    }
}
