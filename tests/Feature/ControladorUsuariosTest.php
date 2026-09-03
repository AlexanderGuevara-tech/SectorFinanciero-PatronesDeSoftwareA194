<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControladorUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_listar_usuarios(): void
    {
        $admin = $this->administradorConManageUsers();
        User::factory()->create(['name' => 'Juan Pérez']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('Juan Pérez')
            ->assertSee('Usuarios');
    }

    public function test_creacion_persiste_usuario_con_password_hasheado_y_roles(): void
    {
        $admin = $this->administradorConManageUsers();
        $rol = Role::create(['name' => 'editor']);
        $email = uniqid('user-').'@test.com';

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
            'roles' => [$rol->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $email]);
        $usuario = User::where('email', $email)->first();
        $this->assertNotEquals('password123', $usuario->password);
        $this->assertTrue($usuario->hasRole('editor'));
    }

    public function test_creacion_rechaza_email_duplicado(): void
    {
        $admin = $this->administradorConManageUsers();
        User::factory()->create(['email' => 'existente@test.com']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Dup User',
            'email' => 'existente@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2); // admin + existing, no new user created
    }

    public function test_creacion_rechaza_password_corto(): void
    {
        $admin = $this->administradorConManageUsers();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Short PW',
            'email' => uniqid('short-').'@test.com',
            'password' => '1234567',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_creacion_rechaza_campos_vacios(): void
    {
        $admin = $this->administradorConManageUsers();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_edicion_persiste_cambios(): void
    {
        $admin = $this->administradorConManageUsers();
        $usuario = User::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $usuario), [
            'name' => 'Actualizado',
            'email' => $usuario->email,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'name' => 'Actualizado']);
    }

    public function test_eliminacion_remueve_usuario_y_filas_role_user_transaccionalmente(): void
    {
        $admin = $this->administradorConManageUsers();
        $rol = Role::create(['name' => 'test-role']);
        $usuario = User::factory()->create();
        $usuario->roles()->attach($rol);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $usuario));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
        $this->assertDatabaseMissing('role_user', ['user_id' => $usuario->id]);
    }

    public function test_auto_descenso_es_rechazado_al_editar_roles(): void
    {
        $admin = $this->administradorConManageUsers();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => [],
        ]);

        $response->assertSessionHasErrors('roles');
        $this->assertTrue($admin->fresh()->hasPermission('manage-users'));
    }

    public function test_eliminar_usuario_propio_es_rechazado(): void
    {
        $admin = $this->administradorConManageUsers();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_ruta_requiere_manage_users(): void
    {
        $sinPermiso = User::factory()->create();

        $response = $this->actingAs($sinPermiso)->get(route('admin.users.index'));

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
