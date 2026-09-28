<?php

namespace App\Application\Account;

interface PuertoKycTransferencia
{
    public function aprobado(TransferirFondosDTO $command): bool;
}
