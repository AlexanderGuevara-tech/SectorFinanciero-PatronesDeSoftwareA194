<?php

namespace Tests\Feature;

use App\Domain\Account\FabricaPaquetesCuentas;
use App\Infrastructure\Persistence\Cliente;
use App\Infrastructure\Persistence\RepositorioCuentasEloquent;
use App\Infrastructure\Persistence\RepositorioIdempotenciaEloquent;
use App\Infrastructure\Persistence\RepositorioLedgerEloquent;
use App\Infrastructure\Persistence\RepositorioOperacionesEloquent;
use App\Infrastructure\Persistence\UnidadDeTrabajoEloquent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LedgerPersistenceTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function it_rolls_back_balance_operation_ledger_and_idempotency_as_one_unit(): void
    {
        [$actor, $source, $destination] = $this->createAccounts();
        $unit = new UnidadDeTrabajoEloquent;

        try {
            $unit->ejecutar(function () use ($actor, $source, $destination): void {
                DB::table('accounts')->whereKey($source)->update(['saldo' => '90.00']);
                $transactionId = DB::table('transactions')->insertGetId([
                    'kind' => 'transfer',
                    'currency' => 'COP',
                    'actor_id' => $actor,
                    'source_account_id' => $source,
                    'destination_account_id' => $destination,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('ledger_lines')->insert([
                    ['transaction_id' => $transactionId, 'account_id' => $source, 'amount' => '-10.00', 'currency' => 'COP', 'actor_id' => $actor, 'created_at' => now(), 'updated_at' => now()],
                    ['transaction_id' => $transactionId, 'account_id' => $destination, 'amount' => '10.00', 'currency' => 'COP', 'actor_id' => $actor, 'created_at' => now(), 'updated_at' => now()],
                ]);
                DB::table('idempotency_keys')->insert([
                    'request_key' => 'rollback-key',
                    'payload_fingerprint' => hash('sha256', 'rollback'),
                    'transaction_id' => $transactionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('force rollback', $exception->getMessage());
        }

        $this->assertSame('100.00', bcadd((string) DB::table('accounts')->where('id', $source)->value('saldo'), '0', 2));
        $this->assertSame(0, DB::table('transactions')->count());
        $this->assertSame(0, DB::table('ledger_lines')->count());
        $this->assertSame(0, DB::table('idempotency_keys')->count());
    }

    #[Test]
    public function it_returns_exact_idempotent_retries_and_rejects_payload_conflicts(): void
    {
        [$actor, $source, $destination] = $this->createAccounts();
        $transactionId = DB::table('transactions')->insertGetId([
            'kind' => 'transfer',
            'currency' => 'COP',
            'actor_id' => $actor,
            'source_account_id' => $source,
            'destination_account_id' => $destination,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $idempotency = new RepositorioIdempotenciaEloquent;
        $fingerprint = hash('sha256', 'same-payload');

        $idempotency->guardar('retry-key', $fingerprint, $transactionId);

        $this->assertSame([
            'request_key' => 'retry-key',
            'payload_fingerprint' => $fingerprint,
            'transaction_id' => $transactionId,
        ], $idempotency->buscar('retry-key'));
        $this->assertFalse($idempotency->conflicto('retry-key', $fingerprint));
        $this->assertTrue($idempotency->conflicto('retry-key', hash('sha256', 'different-payload')));
    }

    #[Test]
    public function it_persists_two_balanced_lines_and_locks_accounts_in_numeric_order(): void
    {
        [$actor, $source, $destination] = $this->createAccounts();
        $operation = new RepositorioOperacionesEloquent;
        $transactionId = $operation->crear([
            'kind' => 'transfer',
            'currency' => 'COP',
            'actor_id' => $actor,
            'source_account_id' => $source,
            'destination_account_id' => $destination,
        ]);
        $ledger = new RepositorioLedgerEloquent;
        $ledger->guardarDosLineas($transactionId, [
            ['account_id' => $source, 'amount' => '-10.00', 'currency' => 'COP', 'actor_id' => $actor],
            ['account_id' => $destination, 'amount' => '10.00', 'currency' => 'COP', 'actor_id' => $actor],
        ]);

        $lines = $ledger->porTransaccion($transactionId);
        $this->assertCount(2, $lines);
        $this->assertSame('0.00', bcadd((string) $lines[0]['amount'], (string) $lines[1]['amount'], 2));
        $this->assertSame([$source, $destination], array_column($lines, 'account_id'));

        $locked = new RepositorioCuentasEloquent(app()->make(FabricaPaquetesCuentas::class));
        $accounts = $locked->porIdsBloqueadas([$destination, $source]);
        $this->assertSame([$source, $destination], array_map(static fn ($account): int => $account->id(), $accounts));
    }

    /** @return array{int, int, int} */
    private function createAccounts(): array
    {
        $actor = User::factory()->create();
        $customer = Cliente::factory()->create();
        $otherCustomer = Cliente::factory()->create();
        $source = DB::table('accounts')->insertGetId([
            'saldo' => '100.00', 'moneda' => 'COP', 'estado' => 'activa', 'tipo' => 'savings',
            'familia' => 'personal', 'customer_id' => $customer->id, 'operado_por' => $actor->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $destination = DB::table('accounts')->insertGetId([
            'saldo' => '0.00', 'moneda' => 'COP', 'estado' => 'activa', 'tipo' => 'savings',
            'familia' => 'personal', 'customer_id' => $otherCustomer->id, 'operado_por' => $actor->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$actor->id, $source, $destination];
    }
}
