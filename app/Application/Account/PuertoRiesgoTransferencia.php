<?php

namespace App\Application\Account;

interface PuertoRiesgoTransferencia
{
    public function seguro(TransferirFondosDTO $command): bool;
}
