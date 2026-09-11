<?php

namespace App\Infrastructure\Persistence;

use App\Application\Account\RepositorioOperaciones;
use Illuminate\Support\Facades\DB;

final class RepositorioOperacionesEloquent implements RepositorioOperaciones
{
    public function porId(int $transactionId): ?array
    {
        $record = DB::table('transactions')->where('id', $transactionId)->first();

        return $record === null ? null : [
            'kind' => $record->kind, 'currency' => $record->currency, 'actor_id' => (int) $record->actor_id,
            'source_account_id' => (int) $record->source_account_id, 'destination_account_id' => (int) $record->destination_account_id,
        ];
    }

    public function tieneReversion(int $transactionId): bool
    {
        return DB::table('transactions')->where('reversal_of_transaction_id', $transactionId)->exists();
    }

    public function crear(array $datos): int
    {
        return (int) DB::table('transactions')->insertGetId([
            ...$datos,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
