<?php

namespace App\Application\Account;

final class TransferenciaWebFacade
{
    public function __construct(private CanalWeb $canalWeb) {}

    public function transferir(
        int $actorId,
        int $sourceAccountId,
        int $destinationAccountId,
        string $currency,
        string $amount,
        string $requestKey,
    ): ResultadoOperacion {
        return $this->canalWeb->transferir(new TransferirFondosDTO(
            actorId: $actorId,
            sourceAccountId: $sourceAccountId,
            destinationAccountId: $destinationAccountId,
            currency: $currency,
            amount: $amount,
            requestKey: $requestKey,
        ));
    }
}
