<?php

namespace App\Application\Account;

final class CanalWeb extends CanalTransferencia
{
    public function codigo(): string
    {
        return 'web';
    }
}
