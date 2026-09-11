<?php

namespace App\Infrastructure\Persistence;

use App\Application\Account\RepositorioLedger;
use Illuminate\Support\Facades\DB;

final class RepositorioLedgerEloquent implements RepositorioLedger
{
    public function guardarDosLineas(int $transactionId, array $lineas): void
    {
        if (count($lineas) !== 2) {
            throw new \InvalidArgumentException('A transaction must have exactly two ledger lines.');
        }

        $currencies = array_unique(array_column($lineas, 'currency'));
        $balance = bcadd((string) $lineas[0]['amount'], (string) $lineas[1]['amount'], 2);
        if (count($currencies) !== 1 || $balance !== '0.00') {
            throw new \InvalidArgumentException('Ledger lines must balance in one currency.');
        }

        DB::table('ledger_lines')->insert(array_map(
            static fn (array $line): array => [
                'transaction_id' => $transactionId,
                ...$line,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $lineas,
        ));
    }

    public function porTransaccion(int $transactionId): array
    {
        return DB::table('ledger_lines')
            ->where('transaction_id', $transactionId)
            ->orderBy('id')
            ->get(['account_id', 'amount', 'currency', 'actor_id'])
            ->map(static fn (object $line): array => [
                'account_id' => (int) $line->account_id,
                'amount' => (string) $line->amount,
                'currency' => $line->currency,
                'actor_id' => (int) $line->actor_id,
            ])
            ->all();
    }
}
