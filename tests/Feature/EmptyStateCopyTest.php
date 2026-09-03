<?php

namespace Tests\Feature;

use App\Application\Account\AbrirCuenta;
use App\Domain\Account\Cuenta;
use App\Domain\Account\FabricaDeCuentas;
use App\Domain\Account\RepositorioCuentas;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyStateCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_estado_vacio_no_afirma_persistence_disabled(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertOk()
            ->assertDontSee('La persistencia de cuentas todavía no está habilitada.')
            ->assertSee('No hay cuentas registradas todavía');
    }

    public function test_estado_vacio_muestra_copia_funcional(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertSee('No hay cuentas registradas todavía')
            ->assertSee('Catálogo de tipos de cuenta');
    }

    public function test_con_cuentas_persistidas_se_muestra_lista(): void
    {
        $usuario = $this->usuarioConPermiso('view-accounts');
        $this->abrirCuenta($usuario->id, 'savings');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertOk()
            ->assertDontSee('No hay cuentas registradas todavía')
            ->assertSee('Cuenta de ahorros')
            ->assertSee('COP');
    }

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'empty-state-'.uniqid()]);
        $permiso = Permission::firstOrCreate(['name' => $nombrePermiso], ['description' => $nombrePermiso]);
        $rol->permissions()->attach($permiso);
        $usuario->roles()->attach($rol);

        return $usuario;
    }

    private function abrirCuenta(int $userId, string $tipo = 'savings'): Cuenta
    {
        $useCase = new AbrirCuenta(
            fabrica: app(FabricaDeCuentas::class),
            repositorio: app(RepositorioCuentas::class),
        );

        return $useCase->ejecutar(tipo: $tipo, userId: $userId);
    }
}
