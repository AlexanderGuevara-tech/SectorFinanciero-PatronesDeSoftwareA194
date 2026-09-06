<?php

namespace App\Domain\Account;

final readonly class PaqueteCuenta
{
    public function __construct(
        public string $familia,
        public CuentaProducto $cuenta,
        public PoliticaComision $comision,
        public PoliticaSobregiro $sobregiro,
    ) {
        if (! in_array($familia, ['personal', 'empresarial'], true)) {
            throw new \InvalidArgumentException("Unknown account family: {$familia}");
        }

        if (
            $cuenta->familia() !== $familia
            || $comision->familia() !== $familia
            || $sobregiro->familia() !== $familia
        ) {
            throw new \InvalidArgumentException('Account package products must belong to the same family.');
        }
    }
}
