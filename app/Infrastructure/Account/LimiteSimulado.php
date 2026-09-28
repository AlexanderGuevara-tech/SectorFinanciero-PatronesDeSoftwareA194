<?php

namespace App\Infrastructure\Account;

use App\Application\Account\PuertoLimiteTransferencia;
use App\Application\Account\TransferirFondosDTO;

final class LimiteSimulado implements PuertoLimiteTransferencia
{
    /** @param array<string, string> $maximumByCurrency Decimal amounts, inclusive. */
    public function __construct(private array $maximumByCurrency = []) {}

    public function permitido(TransferirFondosDTO $command): bool
    {
        return ! isset($this->maximumByCurrency[$command->currency])
            || bccomp($command->amount, $this->maximumByCurrency[$command->currency], 2) <= 0;
    }
}
