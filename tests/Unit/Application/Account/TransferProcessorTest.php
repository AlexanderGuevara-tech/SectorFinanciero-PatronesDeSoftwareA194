<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\AlcanceClientes;
use App\Application\Account\CanalSucursal;
use App\Application\Account\CanalWeb;
use App\Application\Account\PoliticaAutorizacion;
use App\Application\Account\PoliticaValidacionExterna;
use App\Application\Account\ProcesadorControlado;
use App\Application\Account\ProcesadorEstandar;
use App\Application\Account\RepositorioIdempotencia;
use App\Application\Account\RepositorioLedger;
use App\Application\Account\RepositorioOperaciones;
use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferirFondos;
use App\Application\Account\TransferirFondosDTO;
use App\Application\Account\UnidadDeTrabajo;
use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Account\KycSimulado;
use App\Infrastructure\Account\LimiteSimulado;
use App\Infrastructure\Account\RiesgoSimulado;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransferProcessorTest extends TestCase
{
    #[Test]
    public function it_commits_through_the_web_channel_and_rejects_a_stricter_branch_policy_without_ledger_writes(): void
    {
        $source = $this->account(10, '100.00');
        $destination = $this->account(20, '0.00');
        $accounts = $this->createMock(RepositorioCuentas::class);
        $accounts->method('porIdsBloqueadas')->willReturn([$source, $destination]);
        $accounts->expects($this->exactly(2))->method('guardar');
        $scope = $this->createMock(AlcanceClientes::class);
        $scope->method('contiene')->willReturn(true);
        $authorization = $this->createMock(PoliticaAutorizacion::class);
        $unit = $this->createMock(UnidadDeTrabajo::class);
        $unit->method('ejecutar')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $operations = $this->createMock(RepositorioOperaciones::class);
        $operations->expects($this->once())->method('crear')->willReturn(41);
        $ledger = $this->createMock(RepositorioLedger::class);
        $ledger->expects($this->once())->method('guardarDosLineas')->with(41, [
            ['account_id' => 10, 'amount' => '-25.00', 'currency' => 'COP', 'actor_id' => 7],
            ['account_id' => 20, 'amount' => '25.00', 'currency' => 'COP', 'actor_id' => 7],
        ]);
        $keys = $this->createMock(RepositorioIdempotencia::class);
        $keys->method('buscar')->willReturn(null);
        $keys->expects($this->once())->method('guardar');
        $core = new TransferirFondos($unit, $accounts, $authorization, $scope, $operations, $ledger, $keys,
            new PoliticaValidacionExterna(new KycSimulado, new RiesgoSimulado, new LimiteSimulado));

        $approved = (new CanalWeb(new ProcesadorEstandar($core)))->transferir($this->command('approved'));
        $rejected = (new CanalSucursal(new ProcesadorControlado($core,
            new PoliticaValidacionExterna(new KycSimulado, new RiesgoSimulado, new LimiteSimulado(['COP' => '10.00']))
        )))->transferir($this->command('rejected'));

        $this->assertSame(41, $approved->transactionId);
        $this->assertSame(TipoFalloOperacion::TransferLimitExceeded, $rejected->failure);
        $this->assertSame('75.00', $source->saldo());
        $this->assertSame('25.00', $destination->saldo());
    }

    #[Test]
    public function it_rejects_suspected_fraud_before_mutating_accounts_or_creating_ledger_entries(): void
    {
        $source = $this->account(10, '100.00');
        $destination = $this->account(20, '0.00');
        $accounts = $this->createMock(RepositorioCuentas::class);
        $accounts->method('porIdsBloqueadas')->willReturn([$source, $destination]);
        $accounts->expects($this->never())->method('guardar');
        $scope = $this->createMock(AlcanceClientes::class);
        $scope->method('contiene')->willReturn(true);
        $unit = $this->createMock(UnidadDeTrabajo::class);
        $unit->method('ejecutar')->willReturnCallback(static fn (callable $callback): mixed => $callback());
        $operations = $this->createMock(RepositorioOperaciones::class);
        $operations->expects($this->never())->method('crear');
        $ledger = $this->createMock(RepositorioLedger::class);
        $ledger->expects($this->never())->method('guardarDosLineas');
        $keys = $this->createMock(RepositorioIdempotencia::class);
        $keys->method('buscar')->willReturn(null);
        $keys->expects($this->never())->method('guardar');
        $core = new TransferirFondos($unit, $accounts, $this->createMock(PoliticaAutorizacion::class), $scope,
            $operations, $ledger, $keys,
            new PoliticaValidacionExterna(new KycSimulado, new RiesgoSimulado([10]), new LimiteSimulado));

        $result = (new CanalWeb(new ProcesadorEstandar($core)))->transferir($this->command('fraud'));

        $this->assertSame(TipoFalloOperacion::FraudSuspected, $result->failure);
        $this->assertSame('100.00', $source->saldo());
        $this->assertSame('0.00', $destination->saldo());
    }

    private function command(string $key): TransferirFondosDTO
    {
        return new TransferirFondosDTO(7, 10, 20, 'COP', '25.00', $key);
    }

    private function account(int $id, string $balance): Cuenta
    {
        return new Cuenta($balance, Moneda::COP(), EstadoCuenta::Activa, 'savings', $id, 7,
            new CuentaAhorro('personal'), id: $id);
    }
}
