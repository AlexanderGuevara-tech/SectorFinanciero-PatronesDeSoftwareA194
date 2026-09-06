<?php

namespace App\Domain\Account;

interface FabricaFamiliaCuenta
{
    public function familia(): string;

    public function crear(string $tipo): PaqueteCuenta;
}
