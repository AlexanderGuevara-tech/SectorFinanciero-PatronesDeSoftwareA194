<?php

namespace App\Application\Account;

interface RepositorioLedger
{
    /**
     * @param  list<array{account_id: int, amount: string, currency: string, actor_id: int}>  $lineas
     */
    public function guardarDosLineas(int $transactionId, array $lineas): void;

    /**
     * @return list<array{account_id: int, amount: string, currency: string, actor_id: int}>
     */
    public function porTransaccion(int $transactionId): array;
}
