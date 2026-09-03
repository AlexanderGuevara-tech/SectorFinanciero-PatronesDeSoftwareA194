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
    public function ejecutar(int $cuentaId, int $userId, bool $esAdministrador = false): array
    {
        $cuenta = $esAdministrador
            ? $this->repositorio->porId($cuentaId)
            : $this->repositorio->porIdYPropietario($cuentaId, $userId);

        if ($cuenta === null) {
            throw new \InvalidArgumentException('Account not found.');
        }

        return [
            'saldo' => $cuenta->saldo(),
            'moneda' => $cuenta->moneda()->codigo(),
        ];
    }
}
