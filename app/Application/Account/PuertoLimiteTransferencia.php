<?php

namespace App\Application\Account;

interface PuertoLimiteTransferencia
{
    public function permitido(TransferirFondosDTO $command): bool;
}
