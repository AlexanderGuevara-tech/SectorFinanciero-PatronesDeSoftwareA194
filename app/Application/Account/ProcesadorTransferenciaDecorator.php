<?php

namespace App\Application\Account;

abstract class ProcesadorTransferenciaDecorator implements ProcesadorTransferencia
{
    public function __construct(protected ProcesadorTransferencia $processor) {}

    public function ejecutar(TransferirFondosDTO $command): ResultadoOperacion
    {
        return $this->processor->ejecutar($command);
    }
}
