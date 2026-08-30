<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_un_invitado_puede_ver_el_formulario_de_ingreso(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('<html lang="es">', false)
            ->assertSee('Ingreso al Sistema Bancario CORE');
    }

    public function test_un_usuario_puede_ingresar_y_acceder_al_panel(): void
    {
        $usuario = User::factory()->create(['password' => 'password']);

        $response = $this->post(route('login.store'), [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($usuario);
        $this->get(route('dashboard'))->assertOk()->assertSee($usuario->name);
    }

    public function test_las_credenciales_invalidas_son_rechazadas(): void
    {
        $usuario = User::factory()->create(['password' => 'password']);

        $response = $this->post(route('login.store'), [
            'email' => $usuario->email,
            'password' => 'incorrect-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_los_permisos_se_verifican_a_traves_del_rol(): void
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'cashier']);
        $permiso = Permission::create(['name' => 'view-accounts']);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        $this->assertTrue($usuario->hasRole('cashier'));
        $this->assertTrue($usuario->hasPermission('view-accounts'));
        $this->actingAs($usuario)->get('/')->assertOk();
        $this->assertTrue($this->app['auth']->user()->can('view-accounts'));
    }

    public function test_el_contrato_de_rutas_y_el_middleware_de_permisos_permanecen_estables(): void
    {
        $rutas = app('router')->getRoutes()->getRoutesByName();

        $this->assertSame('login', $rutas['login']->uri());
        $this->assertContains('GET', $rutas['login']->methods());
        $this->assertContains('POST', $rutas['login.store']->methods());
        $this->assertSame('dashboard', $rutas['dashboard']->uri());
        $this->assertSame('accounts', $rutas['accounts.index']->uri());
        $this->assertContains('can:view-accounts', $rutas['accounts.index']->middleware());
        $this->assertSame('logout', $rutas['logout']->uri());
        $this->assertContains('POST', $rutas['logout']->methods());
        $this->assertNotContains('GET', $rutas['logout']->methods());
    }

    public function test_el_esquema_de_base_de_datos_permanece_estable(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumn('users', 'email'));
        $this->assertTrue(Schema::hasColumn('users', 'password'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasColumn('roles', 'name'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasColumn('permissions', 'name'));
        $this->assertTrue(Schema::hasTable('role_user'));
        $this->assertTrue(Schema::hasColumn('role_user', 'role_id'));
        $this->assertTrue(Schema::hasColumn('role_user', 'user_id'));
        $this->assertTrue(Schema::hasTable('permission_role'));
        $this->assertTrue(Schema::hasColumn('permission_role', 'permission_id'));
        $this->assertTrue(Schema::hasColumn('permission_role', 'role_id'));
    }
}
