<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Application\Account\ConsultarSaldo;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Persistence\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsultarSaldoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Debería retornar el saldo exacto como string y la moneda explícita COP.
     */
    #[Test]
    public function test_retorna_saldo_exacto_string_y_moneda_cop(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', customerId: $customer->id, operadoPorId: $user->id);

        $consultar = new ConsultarSaldo(
            repositorio: app(RepositorioCuentas::class),
        );
        $resultado = $consultar->ejecutar(cuentaId: $cuenta->id(), customerId: $customer->id);

        $this->assertArrayHasKey('saldo', $resultado);
        $this->assertArrayHasKey('moneda', $resultado);
        $this->assertIsString($resultado['saldo']);
        $this->assertSame('0.00', $resultado['saldo']);
        $this->assertSame('COP', $resultado['moneda']);
    }

    /**
     * Debería retornar el saldo almacenado sin convertirlo a float.
     */
    #[Test]
    public function test_saldo_nunca_es_float(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'checking', customerId: $customer->id, operadoPorId: $user->id);

        $consultar = new ConsultarSaldo(
            repositorio: app(RepositorioCuentas::class),
        );
        $resultado = $consultar->ejecutar(cuentaId: $cuenta->id(), customerId: $customer->id);

        $this->assertIsString($resultado['saldo']);
        $this->assertSame('0.00', $resultado['saldo']);
    }
}
