<?php

namespace Tests\Feature;

use App\Application\Account\PuertoKycTransferencia;
use App\Application\Account\PuertoLimiteTransferencia;
use App\Application\Account\PuertoRiesgoTransferencia;
use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdapterDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/adapters/demo')->assertRedirect(route('login'));
    }

    public function test_user_without_manage_accounts_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/adapters/demo')->assertForbidden();
    }

    public function test_officer_sees_local_simulation_and_all_adapter_outcomes_without_financial_writes(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'adapter-demo-officer']);
        $permission = Permission::firstOrCreate(['name' => 'manage-accounts']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
        // A demo must remain local even if production port bindings change later.
        foreach ([PuertoKycTransferencia::class, PuertoRiesgoTransferencia::class, PuertoLimiteTransferencia::class] as $port) {
            $this->app->bind($port, static fn () => throw new \RuntimeException('A provider binding was resolved by the demo.'));
        }
        $customer = Cliente::factory()->create();
        $accountId = DB::table('accounts')->insertGetId([
            'saldo' => '100.00',
            'moneda' => 'COP',
            'estado' => 'activa',
            'tipo' => 'savings',
            'familia' => 'personal',
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin/adapters/demo?source_account_id='.$accountId.'&amount=100.00');

        $response->assertOk()
            ->assertSee('Simulación local; sin llamadas a proveedores reales')
            ->assertSee('KYC')
            ->assertSee('Riesgo')
            ->assertSee('Límite de transferencia')
            ->assertSee('Aprobada')
            ->assertSee('Rechazo KYC')
            ->assertSee('Riesgo detectado')
            ->assertSee('Límite excedido')
            ->assertSee('No se ejecuta ninguna transferencia')
            ->assertViewHas('escenarios', function (array $scenarios): bool {
                return array_map(static fn (array $scenario): array => [
                    $scenario['kyc'], $scenario['riesgo'], $scenario['limite'], $scenario['resultado'],
                ], $scenarios) === [
                    [true, true, true, 'Validaciones aprobadas'],
                    [false, true, true, 'Rechazo KYC'],
                    [true, false, true, 'Riesgo detectado'],
                    [true, true, false, 'Límite excedido'],
                ];
            });
        $this->assertSame('100.00', bcadd((string) DB::table('accounts')->where('id', $accountId)->value('saldo'), '0', 2));
        $this->assertDatabaseCount('accounts', 1);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('ledger_lines', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }
}
