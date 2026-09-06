<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Application\Account\ListarCuentas;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Persistence\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListarCuentasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Debería ver solo las propias cuentas siendo cliente.
     */
    #[Test]
    public function test_cliente_ve_solo_sus_propias_cuentas(): void
    {
        $this->artisan('migrate');

        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();
        $oficial = User::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $useCase->ejecutar(tipo: 'savings', customerId: $cliente->id, operadoPorId: $oficial->id);
        $useCase->ejecutar(tipo: 'checking', customerId: $otroCliente->id, operadoPorId: $oficial->id);

        $listar = new ListarCuentas(
            repositorio: app(RepositorioCuentas::class),
        );

        $cuentas = $listar->ejecutar(customerId: $cliente->id);

        $this->assertCount(1, $cuentas);
        $this->assertSame($cliente->id, $cuentas[0]->customerId());
    }

    /**
     * Debería ver todas las cuentas siendo administrador.
     */
    #[Test]
    public function test_administrador_ve_todas_las_cuentas(): void
    {
        $this->artisan('migrate');

        $admin = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $otroCliente = Cliente::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $useCase->ejecutar(tipo: 'savings', customerId: $cliente->id, operadoPorId: $admin->id);
        $useCase->ejecutar(tipo: 'checking', customerId: $otroCliente->id, operadoPorId: $admin->id);

        $listar = new ListarCuentas(
            repositorio: app(RepositorioCuentas::class),
        );

        $cuentas = $listar->ejecutar(customerId: $cliente->id);

        $this->assertCount(1, $cuentas);
    }

    /**
     * Debería retornar lista vacía si no hay cuentas.
     */
    #[Test]
    public function test_retorna_lista_vacia_si_no_hay_cuentas(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        $listar = new ListarCuentas(
            repositorio: app(RepositorioCuentas::class),
        );

        $cuentas = $listar->ejecutar(customerId: $customer->id);

        $this->assertCount(0, $cuentas);
    }
}
