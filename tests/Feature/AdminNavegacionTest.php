<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavegacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_con_manage_users_ve_enlaces_en_navegacion(): void
    {
        $admin = $this->usuarioConPermiso('manage-users');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertSee(route('admin.users.index'), false)
            ->assertSee('Usuarios')
            ->assertSee(route('admin.roles.index'), false)
            ->assertSee('Roles');
    }

    public function test_usuario_sin_manage_users_no_ve_enlaces_admin_en_navegacion(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.roles.index'), false);
    }

    public function test_usuario_sin_manage_users_recibe_403_en_ruta_admin_usuarios(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_usuario_sin_manage_users_recibe_403_en_ruta_admin_roles(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('admin.roles.index'));

        $response->assertForbidden();
    }

    public function test_pista_del_dashboard_enlaza_a_admin_users_index(): void
    {
        $admin = $this->usuarioConPermiso('manage-users');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertSee(route('admin.users.index'), false)
            ->assertSee('La administración de usuarios está disponible para vos.');
    }

    public function test_usuario_sin_manage_users_no_ve_pista_admin_en_dashboard(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee('La administración de usuarios está disponible para vos.');
    }

    public function test_administrador_con_manage_users_accede_a_usuarios_index(): void
    {
        $admin = $this->usuarioConPermiso('manage-users');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_administrador_con_manage_users_accede_a_roles_index(): void
    {
        $admin = $this->usuarioConPermiso('manage-users');

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));

        $response->assertOk();
    }

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'admin-nav-test-'.uniqid()]);
        $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }
}
