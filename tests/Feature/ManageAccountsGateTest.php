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

class ManageAccountsGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_con_manage_accounts_esta_autorizado(): void
    {
        $this->artisan('migrate');

        $usuario = $this->usuarioConPermiso('manage-accounts');

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'savings',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', [
            'user_id' => $usuario->id,
            'tipo' => 'savings',
        ]);
    }

    public function test_usuario_sin_manage_accounts_es_denegado_en_escritura(): void
    {
        $this->artisan('migrate');

        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->post(route('accounts.store'), [
            'tipo' => 'savings',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_usuario_sin_manage_accounts_es_denegado_en_bloquear(): void
    {
        $this->artisan('migrate');

        $admin = $this->usuarioConPermisos(['manage-accounts', 'view-accounts']);
        $cuenta = $this->abrirCuenta($admin->id);

        $sinPermiso = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($sinPermiso)->post(route('accounts.block', $cuenta->id()));

        $response->assertForbidden();
    }

    public function test_usuario_sin_manage_accounts_es_denegado_en_desbloquear(): void
    {
        $this->artisan('migrate');

        $admin = $this->usuarioConPermisos(['manage-accounts', 'view-accounts']);
        $cuenta = $this->abrirCuenta($admin->id, 'savings');
        $cuenta->bloquear();
        $this->repositorio()->guardar($cuenta);

        $sinPermiso = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($sinPermiso)->post(route('accounts.unblock', $cuenta->id()));

        $response->assertForbidden();
    }

    public function test_view_accounts_acceso_independiente_para_lectura(): void
    {
        $this->artisan('migrate');

        $usuario = $this->usuarioConPermiso('view-accounts');

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertOk();
    }

    public function test_usuario_sin_view_accounts_es_denegado_en_lectura(): void
    {
        $this->artisan('migrate');

        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('accounts.index'));

        $response->assertForbidden();
    }

    private function usuarioConPermiso(string $nombrePermiso): User
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['name' => 'gate-test-'.uniqid()]);
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
        $rol = Role::create(['name' => 'gate-test-'.uniqid()]);
        foreach ($permisos as $permiso) {
            $rol->permissions()->attach(Permission::firstOrCreate(['name' => $permiso], ['description' => $permiso]));
        }
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

    private function repositorio(): RepositorioCuentas
    {
        return app(RepositorioCuentas::class);
    }
}
