<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Application\Account\ListarCuentas;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\RepositorioCuentas;
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

        $cliente = User::factory()->create();
        $otroCliente = User::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $useCase->ejecutar(tipo: 'savings', userId: $cliente->id);
        $useCase->ejecutar(tipo: 'checking', userId: $otroCliente->id);

        $listar = new ListarCuentas(
            repositorio: app(RepositorioCuentas::class),
        );

        $cuentas = $listar->ejecutar(userId: $cliente->id, esAdministrador: false);

        $this->assertCount(1, $cuentas);
        $this->assertSame($cliente->id, $cuentas[0]->userId());
    }

    /**
     * Debería ver todas las cuentas siendo administrador.
     */
    #[Test]
    public function test_administrador_ve_todas_las_cuentas(): void
    {
        $this->artisan('migrate');

        $admin = User::factory()->create();
        $cliente = User::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $useCase->ejecutar(tipo: 'savings', userId: $admin->id);
        $useCase->ejecutar(tipo: 'checking', userId: $cliente->id);

        $listar = new ListarCuentas(
            repositorio: app(RepositorioCuentas::class),
        );

        $cuentas = $listar->ejecutar(userId: $admin->id, esAdministrador: true);

        $this->assertCount(2, $cuentas);
    }

    /**
     * Debería retornar lista vacía si no hay cuentas.
     */
    #[Test]
    public function test_retorna_lista_vacia_si_no_hay_cuentas(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $listar = new ListarCuentas(
            repositorio: app(RepositorioCuentas::class),
        );

        $cuentas = $listar->ejecutar(userId: $user->id, esAdministrador: false);

        $this->assertCount(0, $cuentas);
    }
}
