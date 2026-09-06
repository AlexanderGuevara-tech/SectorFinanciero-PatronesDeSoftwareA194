<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Persistence\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AbrirCuentaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Debería abrir una cuenta de ahorros válida con saldo 0, COP y estado activa.
     */
    #[Test]
    public function test_abrir_cuenta_ahorro_valida(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $cuenta = $useCase->ejecutar(tipo: 'savings', customerId: $customer->id, operadoPorId: $user->id);

        $this->assertNotNull($cuenta);
        $this->assertSame('0', $cuenta->saldo());
        $this->assertSame('COP', $cuenta->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertSame($customer->id, $cuenta->customerId());
        $this->assertNotNull($cuenta->id());
    }

    /**
     * Debería abrir una cuenta corriente válida con saldo 0 y estado activa.
     */
    #[Test]
    public function test_abrir_cuenta_corriente_valida(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $customer = Cliente::factory()->create();
        $cuenta = $useCase->ejecutar(tipo: 'checking', customerId: $customer->id, operadoPorId: $user->id);

        $this->assertNotNull($cuenta);
        $this->assertSame('0', $cuenta->saldo());
        $this->assertSame('checking', $cuenta->tipo());
        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
    }

    /**
     * Debería rechazar tipo de cuenta desconocido.
     */
    #[Test]
    public function test_rechazar_tipo_desconocido(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown account type: unknown');

        $useCase->ejecutar(tipo: 'unknown', customerId: 1, operadoPorId: $user->id);
    }

    /**
     * Debería persistir la cuenta para recuperarla después.
     */
    #[Test]
    public function test_cuenta_se_persiste_para_recuperarla(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $customer = Cliente::factory()->create();
        $cuenta = $useCase->ejecutar(tipo: 'savings', customerId: $customer->id, operadoPorId: $user->id);

        $repo = app(RepositorioCuentas::class);
        $recuperada = $repo->porId($cuenta->id());

        $this->assertNotNull($recuperada);
        $this->assertSame('0.00', $recuperada->saldo());
        $this->assertSame('savings', $recuperada->tipo());
    }

    #[Test]
    public function test_abrir_cuenta_empresarial_preserva_familia_y_producto(): void
    {
        $this->artisan('migrate');
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        $useCase = new AbrirCuenta(app(FabricaPaquetesCuentas::class), app(RepositorioCuentas::class));

        $cuenta = $useCase->ejecutar(tipo: 'checking', customerId: $customer->id, operadoPorId: $user->id, familia: 'empresarial');

        $this->assertSame('empresarial', $cuenta->familia());
        $this->assertSame('checking', $cuenta->tipo());
    }

    #[Test]
    public function test_rechazar_familia_desconocida_no_persiste(): void
    {
        $this->artisan('migrate');
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        $useCase = new AbrirCuenta(app(FabricaPaquetesCuentas::class), app(RepositorioCuentas::class));

        try {
            $useCase->ejecutar(tipo: 'savings', customerId: $customer->id, operadoPorId: $user->id, familia: 'unknown');
            $this->fail('Unknown family should be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('Unknown account family: unknown', $exception->getMessage());
        }

        $this->assertDatabaseCount('accounts', 0);
    }
}
