<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelPrincipalTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitados_son_redirigidos_desde_ambos_accesos_al_panel(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_usuarios_autenticados_ven_el_shell_compartido_y_el_resumen_de_modulos(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertSee('<nav', false)
            ->assertSee('<main', false)
            ->assertSee($usuario->name)
            ->assertSee('Panel')
            ->assertSee('Cliente')
            ->assertSee('Cuentas')
            ->assertSee('Transacciones')
            ->assertSee('Préstamos')
            ->assertSee('Inversiones')
            ->assertSee('Fraude')
            ->assertSee('Cumplimiento / KYC-AML');
    }

    public function test_las_capacidades_activas_y_los_modulos_planificados_tienen_estados_honestos(): void
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'account-viewer']);
        $permiso = Permission::create(['name' => 'view-accounts']);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertSee('Autenticación y RBAC')
            ->assertSee('Catálogo de tipos de cuenta')
            ->assertSee('Activo')
            ->assertSee('Planificado')
            ->assertSee('solo de referencia')
            ->assertDontSee('$')
            ->assertDontSee('transacciones hoy')
            ->assertDontSee('gráfico');
    }

    public function test_el_modulo_de_cuentas_expone_un_destino_autorizado_de_solo_lectura(): void
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'dashboard-account-viewer']);
        $permiso = Permission::create(['name' => 'view-accounts']);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertSee('href="'.route('accounts.index').'"', false)
            ->assertSee('Vista de cuentas de solo lectura disponible.', false)
            ->assertSee('Activo')
            ->assertDontSee('Saldo total')
            ->assertDontSee('transacciones hoy');
    }

    public function test_el_destino_de_cuentas_esta_oculto_para_usuarios_sin_permiso(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

        $response->assertDontSee('href="'.route('accounts.index').'"', false);
    }

    public function test_los_modulos_planificados_no_se_renderizan_como_enlaces(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertSee('aria-label="Módulo planificado"', false)
            ->assertSee('Aún no disponible')
            ->assertDontSee('href="/customers"', false)
            ->assertDontSee('href="/transactions"', false)
            ->assertSee('href="'.route('dashboard').'"', false);
    }

    public function test_el_shell_marca_el_panel_como_actual_y_el_logout_como_post_protegido_con_csrf(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertSee('aria-current="page"', false)
            ->assertSee('<form method="POST" action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_la_navegacion_del_panel_marca_solo_el_panel_como_actual(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

        $response->assertSee(route('dashboard'), false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('href="'.route('accounts.index').'" aria-current="page"', false);
    }

    public function test_un_usuario_autenticado_puede_cerrar_sesion_por_el_contrato_post_existente(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_el_contenido_sensible_a_permisos_sigue_siendo_especifico_del_usuario_autenticado(): void
    {
        $administrador = User::factory()->create();
        $rol = Role::create(['name' => 'manager']);
        $permiso = Permission::create(['name' => 'manage-users']);
        $rol->permissions()->attach($permiso);
        $administrador->roles()->attach($rol);
        $visitante = User::factory()->create();

        $respuestaAdministrador = $this->actingAs($administrador)->get(route('dashboard'));
        $respuestaVisitante = $this->actingAs($visitante)->get(route('dashboard'));

        $respuestaAdministrador->assertSee('La administración de usuarios está disponible para vos.');
        $respuestaVisitante->assertDontSee('La administración de usuarios está disponible para vos.')
            ->assertDontSee('Catálogo de tipos de cuenta');
    }

    public function test_el_shell_autenticado_expone_la_disclosure_movil_y_el_contenido_principal_en_espanol(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertSee('<html lang="es">', false)
            ->assertSee('<details', false)
            ->assertSee('<summary', false)
            ->assertSee('aria-label="Navegación principal"', false)
            ->assertSee('Abrir navegación')
            ->assertSee('<nav aria-label="Navegación principal">', false)
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('<main id="main-content"', false);
    }

    public function test_la_vista_welcome_expuesta_declara_espanol_y_traduce_el_contenido_por_defecto(): void
    {
        $response = $this->view('welcome');

        $response->assertSee('<html lang="es">', false)
            ->assertSee('Comencemos')
            ->assertSee('Documentación')
            ->assertSee('Implementar ahora')
            ->assertSee('Ver registro de cambios')
            ->assertDontSee('Let\'s get started');
    }
}
