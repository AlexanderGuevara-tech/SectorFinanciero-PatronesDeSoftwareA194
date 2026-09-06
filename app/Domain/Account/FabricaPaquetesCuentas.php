<?php

namespace App\Domain\Account;

interface FabricaPaquetesCuentas
{
    public function crear(string $familia, string $tipo): PaqueteCuenta;
}
