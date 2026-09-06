<?php

namespace Tests\Feature;

use App\Domain\Account\Cuenta;
use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\Moneda;
use App\Domain\Account\PaqueteCuenta;
use App\Domain\Account\PoliticaComisionPorFamilia;
use App\Domain\Account\PoliticaSobregiroPorFamilia;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Persistence\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $customer = Cliente::factory()->create();
        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: $customer->id,
            operadoPorId: $user->id,
            producto: new CuentaAhorro,
        );

        $this->repositorio->guardar($cuenta);
        $recuperada = $this->repositorio->porId($cuenta->id());

        $this->assertNotNull($recuperada);
        $this->assertSame('0.00', $recuperada->saldo());
        $this->assertSame('COP', $recuperada->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $recuperada->estado());
        $this->assertSame('savings', $recuperada->tipo());
        $this->assertSame($customer->id, $recuperada->customerId());
        $this->assertSame($user->id, $recuperada->operadoPorId());
    }

    /**
     * Debería guardar y recuperar una cuenta corriente preservando saldo como string.
     */
    #[Test]
    public function test_guardar_y_recuperar_cuenta_corriente_preserva_saldo(): void
    {
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        $cuenta = new Cuenta(
            saldo: '1500.50',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            customerId: $customer->id,
            operadoPorId: $user->id,
            producto: new CuentaCorriente,
        );

        $this->repositorio->guardar($cuenta);
        $recuperada = $this->repositorio->porId($cuenta->id());

        $this->assertNotNull($recuperada);
        $this->assertSame('1500.50', $recuperada->saldo());
        $this->assertSame('checking', $recuperada->tipo());
    }

    #[Test]
    public function test_guardar_y_recuperar_cuenta_empresarial_preserva_paquete(): void
    {
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        $producto = new CuentaCorriente('empresarial');
        $paquete = new PaqueteCuenta(
            'empresarial',
            $producto,
            new PoliticaComisionPorFamilia('empresarial'),
            new PoliticaSobregiroPorFamilia('empresarial'),
        );
        $cuenta = new Cuenta('1500.50', Moneda::COP(), EstadoCuenta::Activa, 'checking', $customer->id, $user->id, $producto, 'empresarial', $paquete);

        $this->repositorio->guardar($cuenta);
        $recuperada = $this->repositorio->porId($cuenta->id());

        $this->assertSame('empresarial', $recuperada?->familia());
        $this->assertSame('empresarial', $recuperada?->paquete()->sobregiro->familia());
        $this->assertTrue($recuperada?->paquete()->sobregiro->permite($recuperada->producto()));
        $this->assertSame('1500.50', $recuperada?->saldo());
        $this->assertSame('COP', $recuperada?->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $recuperada?->estado());
        $this->assertSame('checking', $recuperada?->tipo());
        $this->assertSame($customer->id, $recuperada?->customerId());
        $this->assertSame($user->id, $recuperada?->operadoPorId());
    }

    #[Test]
    public function test_database_rejects_unknown_persisted_family_before_reconstruction(): void
    {
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        $this->expectException(\Throwable::class);

        DB::table('accounts')->insert([
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'tipo' => 'savings',
            'familia' => 'nonexistent',
        ]);

    }

    #[Test]
    public function test_legacy_row_reconstructs_as_personal_with_existing_state(): void
    {
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        DB::table('accounts')->insert([
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'tipo' => 'savings',
        ]);

        $recuperada = $this->repositorio->porId(1);

        $this->assertSame('personal', $recuperada?->familia());
        $this->assertSame('savings', $recuperada?->tipo());
        $this->assertSame('0.00', $recuperada?->saldo());
        $this->assertSame('COP', $recuperada?->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $recuperada?->estado());
        $this->assertSame($customer->id, $recuperada?->customerId());
        $this->assertSame($user->id, $recuperada?->operadoPorId());
    }

    /**
     * Debería listar solo las cuentas de un cliente específico.
     */
    #[Test]
    public function test_por_cliente_solo_retorna_cuentas_del_cliente(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $customer1 = Cliente::factory()->create();
        $customer2 = Cliente::factory()->create();

        $cuenta1 = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: $customer1->id,
            operadoPorId: $user1->id,
            producto: new CuentaAhorro,
        );
        $cuenta2 = new Cuenta(
            saldo: '500.00',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'checking',
            customerId: $customer2->id,
            operadoPorId: $user2->id,
            producto: new CuentaCorriente,
        );

        $this->repositorio->guardar($cuenta1);
        $this->repositorio->guardar($cuenta2);

        $cuentasCustomer1 = $this->repositorio->porCliente($customer1->id);

        $this->assertCount(1, $cuentasCustomer1);
        $this->assertSame($cuenta1->id(), $cuentasCustomer1[0]->id());
    }

    #[Test]
    public function test_por_cliente_retorna_las_dos_familias_del_cliente_y_excluye_otro_cliente(): void
    {
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();
        $otherCustomer = Cliente::factory()->create();
        $fabrica = app(FabricaPaquetesCuentas::class);

        foreach ([['personal', 'savings'], ['empresarial', 'checking']] as [$familia, $tipo]) {
            $paquete = $fabrica->crear($familia, $tipo);
            $cuenta = new Cuenta(
                saldo: '0',
                moneda: Moneda::COP(),
                estado: EstadoCuenta::Activa,
                tipo: $tipo,
                customerId: $customer->id,
                operadoPorId: $user->id,
                producto: $paquete->cuenta,
                familia: $familia,
                paquete: $paquete,
            );
            $this->repositorio->guardar($cuenta);
        }

        $otherPackage = $fabrica->crear('personal', 'savings');
        $otherAccount = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: $otherCustomer->id,
            operadoPorId: $user->id,
            producto: $otherPackage->cuenta,
            familia: 'personal',
            paquete: $otherPackage,
        );
        $this->repositorio->guardar($otherAccount);

        $accounts = $this->repositorio->porCliente($customer->id);

        $this->assertCount(2, $accounts);
        $this->assertSame(
            ['empresarial', 'personal'],
            collect($accounts)->map(fn (Cuenta $account): string => $account->familia())->sort()->values()->all(),
        );
        $this->assertNotContains(
            $otherAccount->id(),
            collect($accounts)->map(fn (Cuenta $account): ?int => $account->id())->all(),
        );
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
     * Debería retornar null al buscar por ID y cliente con cliente incorrecto.
     */
    #[Test]
    public function test_por_id_y_cliente_retorna_null_si_cliente_no_coincide(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $customer1 = Cliente::factory()->create();
        $customer2 = Cliente::factory()->create();

        $cuenta = new Cuenta(
            saldo: '0',
            moneda: Moneda::COP(),
            estado: EstadoCuenta::Activa,
            tipo: 'savings',
            customerId: $customer1->id,
            operadoPorId: $user1->id,
            producto: new CuentaAhorro,
        );

        $this->repositorio->guardar($cuenta);

        $resultado = $this->repositorio->porIdYCliente($cuenta->id(), $customer2->id);

        $this->assertNull($resultado);
    }
}
