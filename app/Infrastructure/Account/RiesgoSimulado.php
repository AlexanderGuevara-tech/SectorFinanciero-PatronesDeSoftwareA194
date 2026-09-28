<?php

namespace App\Infrastructure\Account;

use App\Application\Account\PuertoRiesgoTransferencia;
use App\Application\Account\TransferirFondosDTO;

final class RiesgoSimulado implements PuertoRiesgoTransferencia
{
    /** @param list<int> $sourceAccountsFlagged */
    public function __construct(private array $sourceAccountsFlagged = []) {}

    public function seguro(TransferirFondosDTO $command): bool
    {
        return ! in_array($command->sourceAccountId, $this->sourceAccountsFlagged, true);
    }
}
