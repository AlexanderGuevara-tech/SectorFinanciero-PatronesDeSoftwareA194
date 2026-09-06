<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControladorClientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_indice_de_clientes_muestra_nombre_documento_y_contacto(): void
    {
        $usuario = $this->usuarioConPermiso('manage-customers');
        Cliente::factory()->create([
            'name' => 'María Gómez',
            'doc_type' => 'CC',
            'doc_number' => '445566',
            'email' => 'maria@example.com',
            'phone' => '3001234567',
        ]);

        $response = $this->actingAs($usuario)->get(route('admin.clientes.index'));

        $response->assertOk()
            ->assertSee('María Gómez')
            ->assertSee('CC 445566')
            ->assertSee('maria@example.com')
            ->assertSee('3001234567');
    }

    public function test_indice_de_clientes_filtra_por_nombre_o_documento(): void
    {
        $usuario = $this->usuarioConPermiso('manage-customers');
        Cliente::factory()->create(['name' => 'María Gómez', 'doc_number' => '445566']);
        Cliente::factory()->create(['name' => 'Luis Pérez', 'doc_number' => '778899']);

        $response = $this->actingAs($usuario)->get(route('admin.clientes.index', ['documento' => '445566']));

        $response->assertOk()
            ->assertSee('María Gómez')
            ->assertDontSee('Luis Pérez');
    }

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'customer-index-'.uniqid()]);
        $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }
}
