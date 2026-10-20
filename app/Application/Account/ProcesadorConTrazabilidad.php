<?php

namespace App\Application\Account;

use Psr\Log\LoggerInterface;
use Throwable;

final class ProcesadorConTrazabilidad extends ProcesadorTransferenciaDecorator
{
    public function __construct(ProcesadorTransferencia $processor, private LoggerInterface $logger)
    {
        parent::__construct($processor);
    }

    public function ejecutar(TransferirFondosDTO $command): ResultadoOperacion
    {
        $this->logger->info('transfer.processing.started');

        $result = parent::ejecutar($command);

        try {
            $this->logger->info('transfer.processing.finished');
        } catch (Throwable) {
            // Logging must not turn a committed transfer into a reported failure.
        }

        return $result;
    }
}
