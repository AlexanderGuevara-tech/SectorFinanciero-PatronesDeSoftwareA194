<?php

namespace Tests\Unit\Application\Account;

use App\Application\Account\AlcanceClientes;
use App\Application\Account\PoliticaAutorizacion;
use App\Application\Account\UnidadDeTrabajo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UnidadDeTrabajoTest extends TestCase
{
    #[Test]
    public function it_defines_transaction_and_authorization_ports(): void
    {
        $unit = new class implements UnidadDeTrabajo
        {
            public function ejecutar(callable $operacion): mixed
            {
                return $operacion();
            }
        };

        $authorization = new class implements PoliticaAutorizacion
        {
            public function autorizar(int $actorId): void {}
        };

        $scope = new class implements AlcanceClientes
        {
            public function contiene(int $actorId, int $customerId): bool
            {
                return $actorId === $customerId;
            }
        };

        $this->assertSame('committed', $unit->ejecutar(fn (): string => 'committed'));
        $authorization->autorizar(7);
        $this->assertTrue($scope->contiene(7, 7));
        $this->assertFalse($scope->contiene(7, 8));
    }
}
