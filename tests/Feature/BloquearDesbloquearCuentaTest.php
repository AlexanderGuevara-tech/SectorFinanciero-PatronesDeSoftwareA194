<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Application\Account\BloquearCuenta;
use App\Application\Account\DesbloquearCuenta;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\RepositorioCuentas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BloquearDesbloquearCuentaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Debería bloquear una cuenta activa.
     */
    #[Test]
    public function test_bloquear_cuenta_activa(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', userId: $user->id);

        $bloquear = new BloquearCuenta(
            repositorio: app(RepositorioCuentas::class),
        );
        $bloqueada = $bloquear->ejecutar(cuentaId: $cuenta->id(), userId: $user->id);

        $this->assertNotNull($bloqueada);
        $this->assertSame(EstadoCuenta::Bloqueada, $bloqueada->estado());
    }

    /**
     * Debería desbloquear una cuenta bloqueada.
     */
    #[Test]
    public function test_desbloquear_cuenta_bloqueada(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', userId: $user->id);

        $bloquear = new BloquearCuenta(
            repositorio: app(RepositorioCuentas::class),
        );
        $bloquear->ejecutar(cuentaId: $cuenta->id(), userId: $user->id);

        $desbloquear = new DesbloquearCuenta(
            repositorio: app(RepositorioCuentas::class),
        );
        $desbloqueada = $desbloquear->ejecutar(cuentaId: $cuenta->id(), userId: $user->id);

        $this->assertNotNull($desbloqueada);
        $this->assertSame(EstadoCuenta::Activa, $desbloqueada->estado());
    }

    /**
     * Una cuenta bloqueada debería rechazar mutaciones: doble bloqueo,
     * doble desbloqueo, y aplicación de saldo.
     */
    #[Test]
    public function test_cuenta_bloqueada_rechaza_escritura(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', userId: $user->id);

        $bloquear = new BloquearCuenta(
            repositorio: app(RepositorioCuentas::class),
        );
        $bloqueada = $bloquear->ejecutar(cuentaId: $cuenta->id(), userId: $user->id);

        // Double-block: already blocked → must refuse
        $this->expectException(\InvalidArgumentException::class);
        $bloqueada->bloquear();
    }

    /**
     * Una cuenta activa debería rechazar desbloqueo redundante.
     */
    #[Test]
    public function test_cuenta_activa_rechaza_desbloqueo_redundante(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', userId: $user->id);

        // Double-unblock: already active → must refuse
        $this->expectException(\InvalidArgumentException::class);
        $cuenta->desbloquear();
    }

    /**
     * Una cuenta bloqueada debería rechazar mutaciones de saldo.
     */
    #[Test]
    public function test_cuenta_bloqueada_rechazar_mutacion_saldo(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', userId: $user->id);

        $bloquear = new BloquearCuenta(
            repositorio: app(RepositorioCuentas::class),
        );
        $bloqueada = $bloquear->ejecutar(cuentaId: $cuenta->id(), userId: $user->id);

        $this->expectException(\InvalidArgumentException::class);
        $bloqueada->aplicarSaldo('500.00');
    }

    /**
     * Debería rechazar bloqueo si la cuenta no pertenece al usuario.
     */
    #[Test]
    public function test_rechazar_bloqueo_cuenta_ajena(): void
    {
        $this->artisan('migrate');

        $propietario = User::factory()->create();
        $otro = User::factory()->create();

        $abrir = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );
        $cuenta = $abrir->ejecutar(tipo: 'savings', userId: $propietario->id);

        $bloquear = new BloquearCuenta(
            repositorio: app(RepositorioCuentas::class),
        );

        $this->expectException(\InvalidArgumentException::class);

        $bloquear->ejecutar(cuentaId: $cuenta->id(), userId: $otro->id);
    }
}
