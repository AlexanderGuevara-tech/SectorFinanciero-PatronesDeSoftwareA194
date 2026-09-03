<?php

namespace Tests\Feature;

use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use App\Domain\Account\RepositorioCuentas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RepositorioCuentasEloquentTest extends TestCase
{
    use RefreshDatabase;

    private RepositorioCuentas $repositorio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositorio = app(RepositorioCuentas::class);
    }

    /**
     * Debería guardar y recuperar una cuenta preservando saldo como string.
     */
    #[Test]
    public function test_guardar_y_recuperar_cuenta_preserva_saldo(): void
    {
        $user = User::factory()->create();
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: $user->id,
            producto: new CuentaAhorro,
        );

        $this->repositorio->guardar($cuenta);
        $recuperada = $this->repositorio->porId($cuenta->id());

        $this->assertNotNull($recuperada);
        $this->assertSame('0.00', $recuperada->saldo());
        $this->assertSame('COP', $recuperada->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $recuperada->estado());
        $this->assertSame('savings', $recuperada->tipo());
        $this->assertSame($user->id, $recuperada->userId());
    }

    /**
     * Debería guardar y recuperar una cuenta corriente preservando saldo como string.
     */
    #[Test]
    public function test_guardar_y_recuperar_cuenta_corriente_preserva_saldo(): void
    {
        $user = User::factory()->create();
        $cuenta = new Cuenta(
            saldo: '1500.50',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            userId: $user->id,
            producto: new CuentaCorriente,
        );

        $this->repositorio->guardar($cuenta);
        $recuperada = $this->repositorio->porId($cuenta->id());

        $this->assertNotNull($recuperada);
        $this->assertSame('1500.50', $recuperada->saldo());
        $this->assertSame('checking', $recuperada->tipo());
    }

    /**
     * Debería listar solo las cuentas de un usuario específico.
     */
    #[Test]
    public function test_por_usuario_solo_retorna_cuentas_del_usuario(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $cuenta1 = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: $user1->id,
            producto: new CuentaAhorro,
        );
        $cuenta2 = new Cuenta(
            saldo: '500.00',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            userId: $user2->id,
            producto: new CuentaCorriente,
        );

        $this->repositorio->guardar($cuenta1);
        $this->repositorio->guardar($cuenta2);

        $cuentasUser1 = $this->repositorio->porUsuario($user1->id);

        $this->assertCount(1, $cuentasUser1);
        $this->assertSame($cuenta1->id(), $cuentasUser1[0]->id());
    }

    /**
     * Debería retornar null al buscar una cuenta por ID inexistente.
     */
    #[Test]
    public function test_por_id_retorna_null_si_no_existe(): void
    {
        $resultado = $this->repositorio->porId(99999);

        $this->assertNull($resultado);
    }

    /**
     * Debería retornar null al buscar por ID y propietario con propietario incorrecto.
     */
    #[Test]
    public function test_por_id_y_propietario_retorna_null_si_propietario_no_coincide(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            userId: $user1->id,
            producto: new CuentaAhorro,
        );

        $this->repositorio->guardar($cuenta);

        $resultado = $this->repositorio->porIdYPropietario($cuenta->id(), $user2->id);

        $this->assertNull($resultado);
    }
}
