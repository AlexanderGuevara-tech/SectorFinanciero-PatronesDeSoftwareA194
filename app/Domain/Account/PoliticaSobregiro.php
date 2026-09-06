<?php

namespace App\Domain\Account;

interface PoliticaSobregiro
{
    public function familia(): string;

    public function permite(CuentaProducto $cuenta): bool;
}
