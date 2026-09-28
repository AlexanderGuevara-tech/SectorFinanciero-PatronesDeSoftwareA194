<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\CanalSucursal;
use App\Application\Account\CanalWeb;
use App\Application\Account\PoliticaValidacionExterna;
use App\Application\Account\ProcesadorTransferencia;
use App\Application\Account\ResultadoOperacion;
use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferirFondosDTO;
use App\Infrastructure\Account\KycSimulado;
use App\Infrastructure\Account\LimiteSimulado;
use App\Infrastructure\Account\RiesgoSimulado;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransferChannelTest extends TestCase
{
    #[Test]
    public function it_translates_configured_provider_decisions_into_specific_rejections(): void
    {
        $command = $this->command();
        $this->assertSame(TipoFalloOperacion::KycRejected, (new PoliticaValidacionExterna(
            new KycSimulado([7]), new RiesgoSimulado, new LimiteSimulado,
        ))->validar($command));
        $this->assertSame(TipoFalloOperacion::FraudSuspected, (new PoliticaValidacionExterna(
            new KycSimulado, new RiesgoSimulado([10]), new LimiteSimulado,
        ))->validar($command));
        $this->assertSame(TipoFalloOperacion::TransferLimitExceeded, (new PoliticaValidacionExterna(
            new KycSimulado, new RiesgoSimulado, new LimiteSimulado(['COP' => '10.00']),
        ))->validar($command));
        $this->assertNull((new PoliticaValidacionExterna(
            new KycSimulado, new RiesgoSimulado, new LimiteSimulado(['COP' => '25.00']),
        ))->validar($command));
    }

    #[Test]
    public function it_combines_either_channel_with_either_processor_without_changing_the_command(): void
    {
        $command = $this->command();
        $processor = $this->createMock(ProcesadorTransferencia::class);
        $processor->expects($this->exactly(2))->method('ejecutar')->with($command)
            ->willReturnOnConsecutiveCalls(
                ResultadoOperacion::committed(31),
                ResultadoOperacion::rejected(TipoFalloOperacion::TransferLimitExceeded),
            );

        $web = new CanalWeb($processor);
        $branch = new CanalSucursal($processor);

        $this->assertSame('web', $web->codigo());
        $this->assertSame('branch', $branch->codigo());
        $this->assertSame(31, $web->transferir($command)->transactionId);
        $this->assertSame(TipoFalloOperacion::TransferLimitExceeded, $branch->transferir($command)->failure);
    }

    #[Test]
    public function it_keeps_the_same_channel_independent_from_the_processor_policy(): void
    {
        $command = $this->command();
        $standardProcessor = $this->createMock(ProcesadorTransferencia::class);
        $standardProcessor->expects($this->once())->method('ejecutar')->with($command)
            ->willReturn(ResultadoOperacion::committed(32));
        $controlledProcessor = $this->createMock(ProcesadorTransferencia::class);
        $controlledProcessor->expects($this->once())->method('ejecutar')->with($command)
            ->willReturn(ResultadoOperacion::rejected(TipoFalloOperacion::FraudSuspected));

        $standardWeb = new CanalWeb($standardProcessor);
        $controlledWeb = new CanalWeb($controlledProcessor);

        $this->assertSame('web', $standardWeb->codigo());
        $this->assertSame('web', $controlledWeb->codigo());
        $this->assertSame(32, $standardWeb->transferir($command)->transactionId);
        $this->assertSame(TipoFalloOperacion::FraudSuspected, $controlledWeb->transferir($command)->failure);
    }

    private function command(): TransferirFondosDTO
    {
        return new TransferirFondosDTO(7, 10, 20, 'COP', '25.00', 'channel-key');
    }
}
