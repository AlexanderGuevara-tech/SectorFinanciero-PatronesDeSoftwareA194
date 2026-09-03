<?php

namespace Tests\Feature;

use App\Domain\Account\CatalogoTiposCuenta;
use App\Domain\Account\CatalogoTiposCuentaEstatico;
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

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'account-viewer-'.uniqid()]);
        $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }
}
