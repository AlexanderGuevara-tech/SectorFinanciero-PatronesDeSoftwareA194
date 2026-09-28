<?php

namespace App\Application\Account;

abstract class CanalTransferencia
{
    public function __construct(protected ProcesadorTransferencia $processor) {}

    abstract public function codigo(): string;

    public function transferir(TransferirFondosDTO $command): ResultadoOperacion
    {
        return $this->processor->ejecutar($command);
    }
}
