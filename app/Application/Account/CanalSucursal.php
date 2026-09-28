<?php

namespace App\Application\Account;

final class CanalSucursal extends CanalTransferencia
{
    public function codigo(): string
    {
        return 'branch';
    }
}
