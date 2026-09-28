<?php

namespace App\Application\Account;

final class ProcesadorEstandar implements ProcesadorTransferencia
{
    public function __construct(private TransferirFondos $transfer) {}

    public function ejecutar(TransferirFondosDTO $command): ResultadoOperacion
    {
        return $this->transfer->ejecutar($command);
    }
}
