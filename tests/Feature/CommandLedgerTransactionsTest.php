<?php

namespace Tests\Feature;

use App\Application\Account\AlcanceClientes;
use App\Application\Account\PoliticaAutorizacion;
use App\Application\Account\PoliticaTransferencia;
use App\Application\Account\PoliticaValidacionExterna;
use App\Application\Account\ResultadoOperacion;
use App\Application\Account\ReversarTransferencia;
use App\Application\Account\ReversarTransferenciaDTO;
use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferirFondos;
use App\Application\Account\TransferirFondosDTO;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Infrastructure\Account\KycSimulado;
use App\Infrastructure\Account\LimiteSimulado;
use App\Infrastructure\Account\RiesgoSimulado;
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

final class CommandLedgerTransactionsTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function it_transfers_atomically_with_two_balanced_lines(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $result = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '25.00', 't-1'));

        $this->assertTrue($result->isCommitted());
        $this->assertSame('75.00', $this->balance($source));
        $this->assertSame('25.00', $this->balance($destination));
        $this->assertCount(2, DB::table('ledger_lines')->where('transaction_id', $result->transactionId)->get());
    }

    #[Test]
    public function it_rejects_business_rules_without_mutating_state(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $this->assertFailure(new TransferirFondosDTO($actor, $source, $source, 'COP', '25.00', 'same'), TipoFalloOperacion::SameAccount);
        $this->assertFailure(new TransferirFondosDTO($actor, $source, $destination, 'USD', '25.00', 'currency'), TipoFalloOperacion::CurrencyMismatch);
        $this->assertFailure(new TransferirFondosDTO($actor, $source, $destination, 'COP', '125.00', 'balance'), TipoFalloOperacion::InsufficientBalance);
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame(0, DB::table('transactions')->count());
    }

    #[Test]
    public function it_rejects_simulated_kyc_and_limit_decisions_without_posting_entries(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $this->app->instance(PoliticaTransferencia::class, new PoliticaValidacionExterna(
            new KycSimulado([$actor]), new RiesgoSimulado, new LimiteSimulado,
        ));
        $kyc = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '25.00', 'kyc'));

        $this->app->instance(PoliticaTransferencia::class, new PoliticaValidacionExterna(
            new KycSimulado, new RiesgoSimulado, new LimiteSimulado(['COP' => '20.00']),
        ));
        $limit = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '25.00', 'limit'));

        $this->assertSame(TipoFalloOperacion::KycRejected, $kyc->failure);
        $this->assertSame(TipoFalloOperacion::TransferLimitExceeded, $limit->failure);
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame('0.00', $this->balance($destination));
        $this->assertSame(0, DB::table('transactions')->count());
        $this->assertSame(0, DB::table('ledger_lines')->count());
        $this->assertSame(0, DB::table('idempotency_keys')->count());
    }

    #[Test]
    public function it_returns_exact_retries_and_rejects_payload_conflicts(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $first = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '10.00', 'retry'));
        $retry = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '10.00', 'retry'));
        $conflict = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '11.00', 'retry'));

        $this->assertSame($first->transactionId, $retry->transactionId);
        $this->assertSame(TipoFalloOperacion::IdempotencyConflict, $conflict->failure);
        $this->assertSame(1, DB::table('transactions')->count());
    }

    #[Test]
    public function it_reverses_once_with_compensating_lines_and_keeps_original_immutable(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $transfer = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '10.00', 'transfer'));
        $reversal = $this->reverse(new ReversarTransferenciaDTO($actor, $transfer->transactionId, 'Customer request', 'reversal'));

        $this->assertTrue($reversal->isCommitted());
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame('0.00', $this->balance($destination));
        $this->assertSame($transfer->transactionId, DB::table('transactions')->where('id', $reversal->transactionId)->value('reversal_of_transaction_id'));
        $this->assertSame(2, DB::table('transactions')->count());
        $retry = $this->reverse(new ReversarTransferenciaDTO($actor, $transfer->transactionId, 'Customer request', 'reversal'));
        $this->assertSame($reversal->transactionId, $retry->transactionId);
        $this->assertSame(TipoFalloOperacion::AlreadyReversed, $this->reverse(new ReversarTransferenciaDTO($actor, $transfer->transactionId, 'Another request', 'reversal-2'))->failure);
    }

    #[Test]
    public function it_rejects_a_conflicting_reversal_payload_without_another_posting(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $transfer = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '10.00', 'transfer-conflict'));
        $reversal = $this->reverse(new ReversarTransferenciaDTO($actor, $transfer->transactionId, 'Customer request', 'reversal-conflict'));

        $conflict = $this->reverse(new ReversarTransferenciaDTO($actor, $transfer->transactionId, 'Different reason', 'reversal-conflict'));

        $this->assertSame(TipoFalloOperacion::IdempotencyConflict, $conflict->failure);
        $this->assertSame($reversal->transactionId, DB::table('idempotency_keys')->where('request_key', 'reversal-conflict')->value('transaction_id'));
        $this->assertSame(2, DB::table('transactions')->count());
        $this->assertSame(4, DB::table('ledger_lines')->count());
    }

    #[Test]
    public function it_exposes_complete_audit_fields_and_preserves_original_lines_after_reversal(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $transfer = $this->transfer(new TransferirFondosDTO($actor, $source, $destination, 'COP', '10.00', 'audit-transfer'));
        $originalBefore = DB::table('ledger_lines')->where('transaction_id', $transfer->transactionId)->orderBy('id')->get()->toArray();

        $reversal = $this->reverse(new ReversarTransferenciaDTO($actor, $transfer->transactionId, 'Audit request', 'audit-reversal'));
        $originalAfter = DB::table('ledger_lines')->where('transaction_id', $transfer->transactionId)->orderBy('id')->get()->toArray();
        $reversalLines = DB::table('ledger_lines')->where('transaction_id', $reversal->transactionId)->orderBy('id')->get();

        $this->assertEquals($originalBefore, $originalAfter);
        $this->assertCount(2, $reversalLines);
        $this->assertSame([$source, $destination], $reversalLines->pluck('account_id')->all());
        $this->assertSame(['10.00', '-10.00'], $reversalLines->map(static fn (object $line): string => bcadd((string) $line->amount, '0', 2))->all());
        $this->assertSame(['COP', 'COP'], $reversalLines->pluck('currency')->all());
        $this->assertSame([$actor, $actor], $reversalLines->pluck('actor_id')->all());
        $this->assertSame([$reversal->transactionId, $reversal->transactionId], $reversalLines->pluck('transaction_id')->all());
    }

    #[Test]
    public function it_rejects_unauthorized_and_out_of_scope_commands_before_mutation(): void
    {
        [$actor, $source, $destination] = $this->accounts();
        $command = new TransferirFondosDTO($actor, $source, $destination, 'COP', '10.00', 'guarded');
        $unauthorized = $this->transferWithPorts($command, new class implements PoliticaAutorizacion
        {
            public function autorizar(int $actorId): void
            {
                throw new \RuntimeException('denied');
            }
        }, new class implements AlcanceClientes
        {
            public function contiene(int $actorId, int $customerId): bool
            {
                return true;
            }
        });
        $outOfScope = $this->transferWithPorts($command, new class implements PoliticaAutorizacion
        {
            public function autorizar(int $actorId): void {}
        }, new class implements AlcanceClientes
        {
            public function contiene(int $actorId, int $customerId): bool
            {
                return false;
            }
        });

        $this->assertSame(TipoFalloOperacion::Unauthorized, $unauthorized->failure);
        $this->assertSame(TipoFalloOperacion::OutOfScope, $outOfScope->failure);
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame(0, DB::table('transactions')->count());
    }

    private function transfer(TransferirFondosDTO $command): ResultadoOperacion
    {
        return $this->transferWithPorts($command, new class implements PoliticaAutorizacion
        {
            public function autorizar(int $actorId): void {}
        }, new class implements AlcanceClientes
        {
            public function contiene(int $actorId, int $customerId): bool
            {
                return true;
            }
        });
    }

    private function transferWithPorts(TransferirFondosDTO $command, PoliticaAutorizacion $authorization, AlcanceClientes $scope): ResultadoOperacion
    {
        return (new TransferirFondos(
            new UnidadDeTrabajoEloquent,
            $this->accountsRepository(),
            $authorization,
            $scope,
            new RepositorioOperacionesEloquent,
            new RepositorioLedgerEloquent,
            new RepositorioIdempotenciaEloquent,
            app(PoliticaTransferencia::class),
        ))->ejecutar($command);
    }

    private function reverse(ReversarTransferenciaDTO $command): ResultadoOperacion
    {
        return (new ReversarTransferencia(
            new UnidadDeTrabajoEloquent, $this->accountsRepository(),
            new class implements PoliticaAutorizacion
            {
                public function autorizar(int $actorId): void {}
            },
            new class implements AlcanceClientes
            {
                public function contiene(int $actorId, int $customerId): bool
                {
                    return true;
                }
            },
            new RepositorioOperacionesEloquent, new RepositorioLedgerEloquent, new RepositorioIdempotenciaEloquent,
        ))->ejecutar($command);
    }

    private function accountsRepository(): RepositorioCuentasEloquent
    {
        return new RepositorioCuentasEloquent(app()->make(FabricaPaquetesCuentas::class));
    }

    /** @return array{int, int, int} */
    private function accounts(): array
    {
        $actor = User::factory()->create();
        $customer = Cliente::factory()->create();
        $other = Cliente::factory()->create();
        $values = static fn (int $customerId, string $balance): int => DB::table('accounts')->insertGetId([
            'saldo' => $balance, 'moneda' => 'COP', 'estado' => 'activa', 'tipo' => 'savings', 'familia' => 'personal',
            'customer_id' => $customerId, 'operado_por' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$actor->id, $values($customer->id, '100.00'), $values($other->id, '0.00')];
    }

    private function balance(int $accountId): string
    {
        return bcadd((string) DB::table('accounts')->where('id', $accountId)->value('saldo'), '0', 2);
    }

    private function assertFailure(TransferirFondosDTO $command, TipoFalloOperacion $failure): void
    {
        $this->assertSame($failure, $this->transfer($command)->failure);
    }
}
