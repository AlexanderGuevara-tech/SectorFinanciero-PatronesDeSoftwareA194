<?php

namespace App\Application\Account;

final class ProcesadorControlado implements ProcesadorTransferencia
{
    public function __construct(private TransferirFondos $transfer, private PoliticaTransferencia $policy) {}

    public function ejecutar(TransferirFondosDTO $command): ResultadoOperacion
    {
        return $this->transfer->ejecutar($command, $this->policy);
    }
}
