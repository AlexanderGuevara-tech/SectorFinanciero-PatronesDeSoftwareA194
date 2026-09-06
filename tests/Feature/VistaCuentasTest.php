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

    public function test_el_indice_muestra_solo_las_cuentas_del_cliente_seleccionado_y_un_solo_nombre(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');
        $clienteSeleccionado = Cliente::factory()->create(['name' => 'Cliente Seleccionado']);
        $otroCliente = Cliente::factory()->create(['name' => 'Otro Cliente']);
        $this->abrirCuenta($clienteSeleccionado->id, $usuario->id);
        $this->abrirCuenta($otroCliente->id, $usuario->id);

        $response = $this->actingAs($usuario)->get(route('accounts.index', ['cliente' => $clienteSeleccionado->id]));
        $contenido = (string) $response->getContent();

        $response->assertOk()
            ->assertSee('Cliente Seleccionado')
            ->assertDontSee('Otro Cliente')
            ->assertDontSee('Cuenta #'.$this->idDeCuenta($otroCliente->id));
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

    public function test_el_detalle_muestra_controles_para_quien_puede_gestionar(): void
    {
        $usuario = $this->usuarioConPermisos(['manage-accounts', 'view-accounts']);
        $cliente = Cliente::factory()->create();
        $cuenta = $this->abrirCuenta($cliente->id, $usuario->id);

        $response = $this->actingAs($usuario)->get(route('accounts.show', $cuenta->id()));

        $response->assertOk()->assertSee('Bloquear')->assertSee('Desbloquear');
    }

    private function abrirCuenta(int $customerId, int $operadoPorId): Cuenta
    {
        return (new AbrirCuenta(
            fabrica: app(FabricaPaquetesCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        ))->ejecutar(tipo: 'savings', customerId: $customerId, operadoPorId: $operadoPorId);
    }

    private function idDeCuenta(int $customerId): int
    {
        return app(RepositorioCuentas::class)->porCliente($customerId)[0]->id();
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
