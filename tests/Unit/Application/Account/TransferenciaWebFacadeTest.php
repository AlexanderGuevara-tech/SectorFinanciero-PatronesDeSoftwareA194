<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\CanalWeb;
use App\Application\Account\ProcesadorTransferencia;
use App\Application\Account\ResultadoOperacion;
use App\Application\Account\TipoFalloOperacion;
use App\Application\Account\TransferenciaWebFacade;
use App\Application\Account\TransferirFondosDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransferenciaWebFacadeTest extends TestCase
{
    #[Test]
    public function it_maps_all_six_inputs_into_one_command_and_returns_the_committed_result_unchanged(): void
    {
        $result = ResultadoOperacion::committed(42);
        $processor = $this->createMock(ProcesadorTransferencia::class);
        $processor->expects($this->once())->method('ejecutar')
            ->with($this->callback(function (TransferirFondosDTO $command): bool {
                $this->assertSame(7, $command->actorId);
                $this->assertSame(10, $command->sourceAccountId);
                $this->assertSame(20, $command->destinationAccountId);
                $this->assertSame('COP', $command->currency);
                $this->assertSame('25.00', $command->amount);
                $this->assertSame('transfer-key', $command->requestKey);

                return true;
            }))->willReturn($result);

        $actual = (new TransferenciaWebFacade(new CanalWeb($processor)))->transferir(
            7, 10, 20, 'COP', '25.00', 'transfer-key',
        );

        $this->assertSame($result, $actual);
    }

    #[Test]
    public function it_returns_the_rejected_result_unchanged(): void
    {
        $result = ResultadoOperacion::rejected(TipoFalloOperacion::FraudSuspected);
        $processor = $this->createMock(ProcesadorTransferencia::class);
        $processor->expects($this->once())->method('ejecutar')->willReturn($result);

        $actual = (new TransferenciaWebFacade(new CanalWeb($processor)))->transferir(
            8, 11, 21, 'USD', '13.50', 'rejected-key',
        );

        $this->assertSame($result, $actual);
    }
}
