<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControladorRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_listar_roles(): void
    {
        $admin = $this->administradorConManageUsers();
        $rol = Role::create(['name' => 'editor']);
        $permiso = Permission::firstOrCreate(['name' => 'manage-users'], ['description' => 'manage-users']);
        $rol->permissions()->attach($permiso);

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));

        $response->assertOk()
            ->assertSee('editor')
            ->assertSee('Roles');
    }

    public function test_creacion_persiste_rol_con_permisos(): void
    {
        $admin = $this->administradorConManageUsers();
        $permiso = Permission::firstOrCreate(['name' => 'view-accounts'], ['description' => 'view-accounts']);

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'editor',
            'description' => 'Rol de editor',
            'permissions' => [$permiso->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'editor']);
        $rol = Role::where('name', 'editor')->first();
        $this->assertTrue($rol->permissions->contains($permiso));
    }

    public function test_creacion_rechaza_nombre_duplicado(): void
    {
        $admin = $this->administradorConManageUsers();
        Role::create(['name' => 'editor']);

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'editor',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_creacion_rechaza_permisos_invalidos(): void
    {
        $admin = $this->administradorConManageUsers();

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'nuevo-rol',
            'permissions' => [99999],
        ]);

        $response->assertSessionHasErrors('permissions.0');
    }

    public function test_edicion_persiste_cambios(): void
    {
        $admin = $this->administradorConManageUsers();
        $rol = Role::create(['name' => 'editor']);
        $permiso = Permission::firstOrCreate(['name' => 'manage-accounts'], ['description' => 'manage-accounts']);

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $rol), [
            'name' => 'editor senior',
            'permissions' => [$permiso->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $rol->id, 'name' => 'editor senior']);
        $rol->refresh();
        $this->assertTrue($rol->permissions->contains($permiso));
    }

    public function test_eliminacion_remueve_rol_y_filas_permission_role(): void
    {
        $admin = $this->administradorConManageUsers();
        $rol = Role::create(['name' => 'temp-role']);
        $permiso = Permission::firstOrCreate(['name' => 'view-accounts'], ['description' => 'view-accounts']);
        $rol->permissions()->attach($permiso);

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $rol));

        $response->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
        $this->assertDatabaseMissing('permission_role', ['role_id' => $rol->id]);
    }

    public function test_eliminar_rol_administrator_es_rechazado(): void
    {
        $admin = $this->administradorConManageUsers();
        $adminRole = Role::where('name', 'administrator')->first();

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $adminRole));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
    }

    public function test_eliminar_rol_con_manage_users_del_admin_es_rechazado(): void
    {
        $admin = $this->administradorConManageUsers();
        $manageUsers = Permission::firstOrCreate(['name' => 'manage-users'], ['description' => 'manage-users']);
        $rol = Role::create(['name' => 'super-admin']);
        $rol->permissions()->attach($manageUsers);
        $admin->roles()->attach($rol);

        $response = $this->actingAs($admin)->delete(route('admin.roles.destroy', $rol));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    public function test_quitar_manage_users_de_rol_del_admin_cuando_es_su_ultima_fuente_es_rechazado(): void
    {
        $admin = $this->administradorConManageUsers();
        $manageUsers = Permission::firstOrCreate(['name' => 'manage-users'], ['description' => 'manage-users']);
        $adminRole = Role::where('name', 'administrator')->first();

        $response = $this->actingAs($admin)->put(route('admin.roles.update', $adminRole), [
            'name' => 'administrator',
            'permissions' => [],
        ]);

        $response->assertSessionHasErrors('permissions');
        $this->assertTrue($admin->fresh()->hasPermission('manage-users'));
    }

    public function test_ruta_requiere_manage_users(): void
    {
        $sinPermiso = User::factory()->create();

        $response = $this->actingAs($sinPermiso)->get(route('admin.roles.index'));

        $response->assertForbidden();
    }

    private function administradorConManageUsers(): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'administrator']);
        $permiso = Permission::firstOrCreate(['name' => 'manage-users'], ['description' => 'manage-users']);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }
}
