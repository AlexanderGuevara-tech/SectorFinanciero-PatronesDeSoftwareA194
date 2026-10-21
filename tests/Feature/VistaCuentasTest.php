<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\CatalogoTiposCuentaEstatico;
use App\Domain\Account\Cuenta;
use App\Domain\Account\FabricaPaquetesCuentas;
use App\Domain\Account\RepositorioCuentas;
use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VistaCuentasTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitados_son_redirigidos_al_ingreso(): void
    {
        $this->get(route('accounts.index'))->assertRedirect(route('login'));
    }

    public function test_usuarios_autenticados_sin_permiso_reciben_prohibido(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('accounts.index'));

        $response->assertForbidden()->assertDontSee('Catálogo de tipos de cuenta');
    }

    public function test_usuarios_autorizados_ven_la_vista_de_cuentas(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertOk()
            ->assertSee('Cuentas')
            ->assertSee('No hay cuentas registradas todavía');
    }

    public function test_la_vista_renderiza_metadatos_exactos_del_catalogo_en_espanol(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertSee('savings')
            ->assertSee('Cuenta de ahorros')
            ->assertSee('checking')
            ->assertSee('Cuenta corriente')
            ->assertSee('COP')
            ->assertSee('USD')
            ->assertSee('Sobregiro no permitido')
            ->assertSee('Sobregiro permitido')
            ->assertDontSee('checking-extra');
    }

    public function test_el_estado_vacio_muestra_mensaje_funcional(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertSee('No hay cuentas registradas todavía')
            ->assertSee('Crea una cuenta bancaria para comenzar a operar.')
            ->assertDontSee('La persistencia de cuentas todavía no está habilitada.');
    }

    public function test_la_evidencia_del_singleton_muestra_la_identidad_real_del_contenedor(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');
        $primeraEsperada = $this->app->make(CatalogoTiposCuenta::class);
        $segundaEsperada = $this->app->make(CatalogoTiposCuenta::class);

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertSee(CatalogoTiposCuenta::class)
            ->assertSee(CatalogoTiposCuentaEstatico::class)
            ->assertSee($primeraEsperada === $segundaEsperada ? 'Sí, es la misma instancia.' : 'No, son instancias diferentes.')
            ->assertSee('metadatos de referencia inmutables')
            ->assertSee('no contiene cuentas, saldos ni estado de la solicitud');
    }

    public function test_la_navegacion_de_cuentas_marca_actual_sin_marcar_el_panel(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertSee(route('accounts.index'), false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('href="'.route('dashboard').'" aria-current="page"', false)
            ->assertSee('<main id="main-content"', false);
    }

    public function test_la_navegacion_de_cuentas_incluye_todas_las_pantallas_segun_permisos(): void
    {
        $usuario = $this->usuarioConPermisos(['view-accounts', 'manage-users', 'manage-customers', 'manage-accounts']);

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        foreach (['dashboard', 'accounts.index', 'admin.users.index', 'admin.roles.index', 'admin.clientes.index', 'admin.adapters.demo'] as $nombreRuta) {
            $response->assertSee(route($nombreRuta), false);
        }
    }

    public function test_la_navegacion_de_cuentas_oculta_paginas_sin_su_permiso(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        foreach (['admin.users.index', 'admin.roles.index', 'admin.clientes.index', 'admin.adapters.demo'] as $nombreRuta) {
            $response->assertDontSee(route($nombreRuta), false);
        }
    }

    public function test_administrator_sees_accounts_from_multiple_customers_without_filter(): void
    {
        $administrator = $this->usuarioAdministrador();
        $firstCustomer = Cliente::factory()->create(['name' => 'First Account Owner']);
        $secondCustomer = Cliente::factory()->create(['name' => 'Second Account Owner']);
        $firstAccount = $this->abrirCuenta($firstCustomer->id, $administrator->id);
        $secondAccount = $this->abrirCuenta($secondCustomer->id, $administrator->id);

        $this->actingAs($administrator)->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('>#'.$firstAccount->id().'</dd>', false)
            ->assertSee('>#'.$secondAccount->id().'</dd>', false)
            ->assertSee('First Account Owner')
            ->assertSee('Second Account Owner');
    }

    public function test_customer_cannot_broaden_account_visibility_with_cliente_query(): void
    {
        $ownCustomer = Cliente::factory()->create(['name' => 'Own Account Owner']);
        $otherCustomer = Cliente::factory()->create(['name' => 'Other Account Owner']);
        $customerUser = $this->usuarioConPermiso('view-accounts');
        $customerUser->customer()->associate($ownCustomer);
        $customerUser->save();
        $ownAccount = $this->abrirCuenta($ownCustomer->id, $customerUser->id);
        $otherAccount = $this->abrirCuenta($otherCustomer->id, $customerUser->id);

        foreach ([[], ['cliente' => $otherCustomer->id], ['cliente' => 0]] as $query) {
            $this->actingAs($customerUser)->get(route('accounts.index', $query))
                ->assertOk()
                ->assertSee('>#'.$ownAccount->id().'</dd>', false)
                ->assertDontSee('>#'.$otherAccount->id().'</dd>', false)
                ->assertDontSee('Other Account Owner');
        }
    }

    public function test_user_without_customer_cannot_use_cliente_to_view_accounts(): void
    {
        $user = $this->usuarioConPermiso('view-accounts');
        $otherCustomer = Cliente::factory()->create(['name' => 'Unrelated Account Owner']);
        $otherAccount = $this->abrirCuenta($otherCustomer->id, $user->id);

        $this->actingAs($user)->get(route('accounts.index', ['cliente' => $otherCustomer->id]))
            ->assertOk()
            ->assertSee('No hay cuentas registradas todavía')
            ->assertDontSee('>#'.$otherAccount->id().'</dd>', false)
            ->assertDontSee('Unrelated Account Owner');
    }

    public function test_el_indice_muestra_solo_las_cuentas_del_cliente_seleccionado_y_un_solo_nombre(): void
    {
        $usuario = $this->usuarioAdministrador();
        $clienteSeleccionado = Cliente::factory()->create(['name' => 'Cliente Seleccionado']);
        $otroCliente = Cliente::factory()->create(['name' => 'Otro Cliente']);
        $this->abrirCuenta($clienteSeleccionado->id, $usuario->id);
        $otraCuenta = $this->abrirCuenta($otroCliente->id, $usuario->id);

        $response = $this->actingAs($usuario)->get(route('accounts.index', ['cliente' => $clienteSeleccionado->id]));
        $contenido = (string) $response->getContent();

        $response->assertOk()
            ->assertSee('Cliente Seleccionado')
            ->assertDontSee('Otro Cliente')
            ->assertDontSee('>#'.$otraCuenta->id().'</dd>', false);
        self::assertSame(1, substr_count($contenido, 'Cliente Seleccionado'));
    }

    public function test_el_detalle_muestra_saldo_moneda_y_nombre_del_cliente(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');
        $cliente = Cliente::factory()->create(['name' => 'Cliente del Detalle']);
        $cuenta = $this->abrirCuenta($cliente->id, $usuario->id);

        $response = $this->actingAs($usuario)->get(route('accounts.show', $cuenta->id()));

        $response->assertOk()
            ->assertSee('COP 0.00')
            ->assertSee('Cliente: Cliente del Detalle');
    }

    public function test_el_formulario_permite_seleccionar_familia_personal_o_empresarial_con_personal_por_defecto(): void
    {
        $usuario = $this->usuarioConPermisos(['manage-accounts', 'view-accounts']);

        $this->actingAs($usuario)->get(route('accounts.index'))
            ->assertSee('<select id="familia" name="familia"', false)
            ->assertSee('<option value="personal" selected>Personal</option>', false)
            ->assertSee('<option value="empresarial" >Empresarial</option>', false);
    }

    public function test_la_familia_elegida_se_persiste_y_se_muestra_en_listado_y_detalle(): void
    {
        $usuario = $this->usuarioConPermisos(['manage-accounts', 'view-accounts']);
        $cliente = Cliente::factory()->create();
        $usuario->customer()->associate($cliente);
        $usuario->save();

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'checking',
            'familia' => 'empresarial',
            'customer_id' => $cliente->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', ['customer_id' => $cliente->id, 'familia' => 'empresarial']);
        $this->actingAs($usuario)->get(route('accounts.index'))
            ->assertSee('Familia')
            ->assertSee('Empresarial');
        $this->actingAs($usuario)->get($response->headers->get('Location'))
            ->assertSee('Familia: Empresarial');
    }

    public function test_la_familia_personal_predeterminada_se_muestra_en_listado_y_detalle(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');
        $cliente = Cliente::factory()->create();
        $usuario->customer()->associate($cliente);
        $usuario->save();
        $cuenta = $this->abrirCuenta($cliente->id, $usuario->id);

        $this->actingAs($usuario)->get(route('accounts.index'))
            ->assertSee('Familia')
            ->assertSee('Personal');
        $this->actingAs($usuario)->get(route('accounts.show', $cuenta->id()))
            ->assertSee('Familia: Personal');
    }

    public function test_el_detalle_muestra_controles_para_quien_puede_gestionar(): void
    {
        $usuario = $this->usuarioConPermisos(['manage-accounts', 'view-accounts']);
        $cliente = Cliente::factory()->create();
        $cuenta = $this->abrirCuenta($cliente->id, $usuario->id);

        $response = $this->actingAs($usuario)->get(route('accounts.show', $cuenta->id()));

        $response->assertOk()->assertSee('Bloquear')->assertSee('Desbloquear');
    }

    private function usuarioAdministrador(): User
    {
        $usuario = User::factory()->create();
        $rol = Role::firstOrCreate(['name' => 'administrator'], ['description' => 'Administrator']);
        $permiso = Permission::firstOrCreate(['name' => 'view-accounts'], ['description' => 'view-accounts']);
        $rol->permissions()->syncWithoutDetaching([$permiso->id]);
        $usuario->roles()->attach($rol);

        return $usuario;
    }

    private function abrirCuenta(int $customerId, int $operadoPorId): Cuenta
    {
        return (new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        ))->ejecutar(tipo: 'savings', customerId: $customerId, operadoPorId: $operadoPorId);
    }

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'account-viewer-'.uniqid()]);
        $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }

    /**
     * @param  list<string>  $permisos
     */
    private function usuarioConPermisos(array $permisos): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'account-viewer-'.uniqid()]);

        foreach ($permisos as $nombrePermiso) {
            $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
            $rol->permissions()->attach($permiso);
        }

        $usuario->roles()->attach($rol);

        return $usuario;
    }
}
