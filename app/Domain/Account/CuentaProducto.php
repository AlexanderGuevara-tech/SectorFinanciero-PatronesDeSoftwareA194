<?php

namespace App\Domain\Account;

interface CuentaProducto
{
    public function permiteSobregiro(): bool;

    /**
     * Apply a saldo delta and return the resulting saldo as a string.
     *
     * @throws \InvalidArgumentException if the operation violates the product's overdraft policy.
     */
    public function aplicaSaldo(string $saldoActual, string $delta): string;
}
