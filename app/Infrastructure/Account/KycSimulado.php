<?php

namespace App\Infrastructure\Account;

use App\Application\Account\PuertoKycTransferencia;
use App\Application\Account\TransferirFondosDTO;

final class KycSimulado implements PuertoKycTransferencia
{
    /** @param list<int> $actorsRejected */
    public function __construct(private array $actorsRejected = []) {}

    public function aprobado(TransferirFondosDTO $command): bool
    {
        return ! in_array($command->actorId, $this->actorsRejected, true);
    }
}
