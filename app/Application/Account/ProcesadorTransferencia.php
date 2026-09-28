<?php

namespace App\Application\Account;

interface ProcesadorTransferencia
{
    public function ejecutar(TransferirFondosDTO $command): ResultadoOperacion;
}
