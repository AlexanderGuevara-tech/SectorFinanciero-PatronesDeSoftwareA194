<?php

namespace App\Domain\Account;

interface FabricaDeCuentas
{
    public function crear(string $tipo): ?CuentaProducto;
}
