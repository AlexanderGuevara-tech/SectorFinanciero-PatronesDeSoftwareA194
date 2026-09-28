<?php

namespace App\Application\Account;

interface PoliticaTransferencia
{
    public function validar(TransferirFondosDTO $command): ?TipoFalloOperacion;
}
