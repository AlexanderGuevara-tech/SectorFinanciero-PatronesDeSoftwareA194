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
     * The repository contract exposes exactly six methods.
     */
    #[Test]
    public function test_is_an_interface_with_the_six_contract_methods(): void
    {
        $reflection = new ReflectionClass(RepositorioCuentas::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertTrue($reflection->isAbstract());

        $metodosEsperados = ['guardar', 'porId', 'porCliente', 'todos', 'porIdYCliente', 'porIdsBloqueadas'];
        $metodosReales = array_map(
            fn (\ReflectionMethod $m): string => $m->getName(),
            $reflection->getMethods(),
        );

        $this->assertSame($metodosEsperados, array_values($metodosReales));
    }

    /**
     * Any adapter implementing all six methods satisfies the repository contract.
     */
    #[Test]
    public function test_accepts_any_class_implementing_the_six_methods(): void
    {
        $adapter = new class implements RepositorioCuentas
        {
            public function guardar(Cuenta $cuenta): void {}

            public function porId(int $id): ?Cuenta
            {
                return null;
            }

            public function porCliente(int $customerId): array
            {
                return [];
            }

            public function todos(): array
            {
                return [];
            }

            public function porIdYCliente(int $id, int $customerId): ?Cuenta
            {
                return null;
            }

            public function porIdsBloqueadas(array $ids): array
            {
                return [];
            }
        };

        $this->assertInstanceOf(RepositorioCuentas::class, $adapter);
    }
}
