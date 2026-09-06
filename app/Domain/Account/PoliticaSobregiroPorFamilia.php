<?php

namespace App\Domain\Account;

final readonly class PoliticaSobregiroPorFamilia implements PoliticaSobregiro
{
    public function __construct(private string $identificadorFamilia) {}

    public function familia(): string
    {
        return $this->identificadorFamilia;
    }

    public function permite(CuentaProducto $cuenta): bool
    {
        return $cuenta->permiteSobregiro();
    }
}
