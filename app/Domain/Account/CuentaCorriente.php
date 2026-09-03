<?php

namespace App\Domain\Account;

final class CuentaCorriente implements CuentaProducto
{
    private const SOBREGIRO_LIMITE = '1000.00';

    public function permiteSobregiro(): bool
    {
        return true;
    }

    public function aplicaSaldo(string $saldoActual, string $delta): string
    {
        $resultado = bcadd($saldoActual, $delta, 2);

        if (bccomp($resultado, '-'.self::SOBREGIRO_LIMITE, 2) < 0) {
            throw new \InvalidArgumentException(
                'Checking account overdraft exceeds the limit of '.self::SOBREGIRO_LIMITE.'.'
            );
        }

        return $resultado;
    }
}
