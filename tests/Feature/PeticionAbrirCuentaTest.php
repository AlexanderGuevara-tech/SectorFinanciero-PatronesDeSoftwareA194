<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeticionAbrirCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_abre_cuenta_para_cliente_existente_seleccionado(): void
    {
        $usuario = $this->usuarioConPermiso('manage-accounts');
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'savings',
            'customer_id' => $cliente->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', [
            'customer_id' => $cliente->id,
            'operado_por' => $usuario->id,
            'familia' => 'personal',
        ]);
    }

    public function test_abre_cuenta_empresarial_para_cliente_seleccionado(): void
    {
        $usuario = $this->usuarioConPermiso('manage-accounts');
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'checking',
            'familia' => 'empresarial',
            'customer_id' => $cliente->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', ['customer_id' => $cliente->id, 'familia' => 'empresarial']);
    }

    public function test_rejects_unknown_family_without_writing_an_account(): void
    {
        $usuario = $this->usuarioConPermiso('manage-accounts');
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'savings',
            'familia' => 'unknown',
            'customer_id' => $cliente->id,
        ]);

        $response->assertSessionHasErrors('familia');
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_crea_cliente_inline_y_abre_cuenta_con_su_identificador(): void
    {
        $usuario = $this->usuarioConPermiso('manage-accounts');

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'checking',
            'name' => 'Ana Torres',
            'doc_type' => 'CC',
            'doc_number' => '9001',
            'email' => 'ana@example.com',
            'phone' => '3005550101',
        ]);

        $response->assertRedirect();
        $cliente = Cliente::query()->where('doc_type', 'CC')->where('doc_number', '9001')->firstOrFail();

        $this->assertDatabaseHas('accounts', [
            'customer_id' => $cliente->id,
            'operado_por' => $usuario->id,
            'tipo' => 'checking',
        ]);
    }

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'inline-account-'.uniqid()]);
        $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }
}
