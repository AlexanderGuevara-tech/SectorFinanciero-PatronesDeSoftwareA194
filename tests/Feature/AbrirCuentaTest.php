<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\RepositorioCuentas;
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

        $useCase = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $cuenta = $useCase->ejecutar(tipo: 'savings', userId: $user->id);

        $this->assertNotNull($cuenta);
        $this->assertSame('0', $cuenta->saldo());
        $this->assertSame('COP', $cuenta->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $cuenta->estado());
        $this->assertSame($user->id, $cuenta->userId());
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
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $cuenta = $useCase->ejecutar(tipo: 'checking', userId: $user->id);

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
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown account type: unknown');

        $useCase->ejecutar(tipo: 'unknown', userId: $user->id);
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
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        $cuenta = $useCase->ejecutar(tipo: 'savings', userId: $user->id);

        $repo = app(RepositorioCuentas::class);
        $recuperada = $repo->porId($cuenta->id());

        $this->assertNotNull($recuperada);
        $this->assertSame('0.00', $recuperada->saldo());
        $this->assertSame('savings', $recuperada->tipo());
    }
}
