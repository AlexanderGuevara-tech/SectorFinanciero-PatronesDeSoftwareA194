<?php

namespace App\Application\Account;

final class PoliticaValidacionExterna implements PoliticaTransferencia
{
    public function __construct(
        private PuertoKycTransferencia $kyc,
        private PuertoRiesgoTransferencia $risk,
        private PuertoLimiteTransferencia $limit,
    ) {}

    public function validar(TransferirFondosDTO $command): ?TipoFalloOperacion
    {
        if (! $this->kyc->aprobado($command)) {
            return TipoFalloOperacion::KycRejected;
        }
        if (! $this->risk->seguro($command)) {
            return TipoFalloOperacion::FraudSuspected;
        }
        if (! $this->limit->permitido($command)) {
            return TipoFalloOperacion::TransferLimitExceeded;
        }

        return null;
    }
}
