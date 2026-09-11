<?php

namespace App\Application\Account;

interface RepositorioOperaciones
{
    /** @return array{kind: string, currency: string, actor_id: int, source_account_id: int, destination_account_id: int}|null */
    public function porId(int $transactionId): ?array;

    public function tieneReversion(int $transactionId): bool;

    /**
     * @param  array{kind: string, currency: string, actor_id: int, source_account_id: int, destination_account_id: int, reversal_of_transaction_id?: int|null}  $datos
     */
    public function crear(array $datos): int;
}
