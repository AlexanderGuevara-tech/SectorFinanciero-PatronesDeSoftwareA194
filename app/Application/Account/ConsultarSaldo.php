<?php

namespace App\Application\Account;

use App\Domain\Account\RepositorioCuentas;

final class ConsultarSaldo
{
    public function __construct(
        private RepositorioCuentas $repositorio,
    ) {}

    /**
     * @return array{saldo: string, moneda: string}
     */
    public function ejecutar(int $cuentaId, int $customerId): array
    {
        $cuenta = $this->repositorio->porIdYCliente($cuentaId, $customerId);

        if ($cuenta === null) {
            throw new \InvalidArgumentException('Account not found.');
        }

        return [
            'saldo' => $cuenta->saldo(),
            'moneda' => $cuenta->moneda()->codigo(),
        ];
    }
}
