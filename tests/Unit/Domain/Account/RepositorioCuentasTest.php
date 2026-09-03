<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\Cuenta;
use App\Domain\Account\RepositorioCuentas;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RepositorioCuentasTest extends TestCase
{
    /**
     * Debería ser un interfaz con exactamente los cinco métodos del contrato.
     */
    #[Test]
    public function test_is_an_interface_with_the_five_contract_methods(): void
    {
        $reflection = new ReflectionClass(RepositorioCuentas::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertTrue($reflection->isAbstract());

        $metodosEsperados = ['guardar', 'porId', 'porUsuario', 'todos', 'porIdYPropietario'];
        $metodosReales = array_map(
            fn (\ReflectionMethod $m): string => $m->getName(),
            $reflection->getMethods(),
        );

        $this->assertSame($metodosEsperados, array_values($metodosReales));
    }

    /**
     * Debería aceptar cualquier clase que implemente los cinco métodos.
     */
    #[Test]
    public function test_accepts_any_class_implementing_the_five_methods(): void
    {
        $adapter = new class implements RepositorioCuentas
        {
            public function guardar(Cuenta $cuenta): void {}

            public function porId(int $id): ?Cuenta
            {
                return null;
            }

            public function porUsuario(int $userId): array
            {
                return [];
            }

            public function todos(): array
            {
                return [];
            }

            public function porIdYPropietario(int $id, int $userId): ?Cuenta
            {
                return null;
            }
        };

        $this->assertInstanceOf(RepositorioCuentas::class, $adapter);
    }
}
