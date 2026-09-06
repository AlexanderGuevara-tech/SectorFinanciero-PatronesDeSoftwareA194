<?php

namespace App\Domain\Account;

final class CuentaAhorro implements CuentaProducto
{
    public function __construct(private string $identificadorFamilia = 'personal') {}

    public function familia(): string
    {
        return $this->identificadorFamilia;
    }

    public function conFamilia(string $familia): self
    {
        return new self($familia);
    }

    public function permiteSobregiro(): bool
    {
        return false;
    }

    public function aplicaSaldo(string $saldoActual, string $delta): string
    {
        $resultado = bcadd($saldoActual, $delta, 2);

        if ($resultado[0] === '-') {
            throw new \InvalidArgumentException('Savings account does not allow negative balance.');
        }

        return $resultado;
    }
}
