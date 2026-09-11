<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficerLedgerRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_officer_can_transfer_through_the_route(): void
    {
        $officer = $this->officer();
        [$source, $destination] = $this->accountsOperatedBy($officer);

        $response = $this->actingAs($officer)->post(route('accounts.transfer'), [
            'source_account_id' => $source,
            'destination_account_id' => $destination,
            'currency' => 'COP',
            'amount' => '25.00',
            'request_key' => 'route-transfer-1',
        ]);

        $response->assertRedirect();
        $this->assertSame('75.00', $this->balance($source));
        $this->assertSame('25.00', $this->balance($destination));
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('ledger_lines', 2);
    }

    #[Test]
    public function an_authorized_officer_can_reverse_a_transfer_through_the_route(): void
    {
        $officer = $this->officer();
        [$source, $destination] = $this->accountsOperatedBy($officer);
        $transfer = $this->actingAs($officer)->post(route('accounts.transfer'), [
            'source_account_id' => $source,
            'destination_account_id' => $destination,
            'currency' => 'COP',
            'amount' => '25.00',
            'request_key' => 'route-reversal-transfer',
        ]);
        $transactionId = (int) DB::table('transactions')->value('id');

        $response = $this->actingAs($officer)->post(route('accounts.reverse', $transactionId), [
            'reason' => 'Customer request',
            'request_key' => 'route-reversal-1',
        ]);

        $transfer->assertRedirect();
        $response->assertRedirect();
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame('0.00', $this->balance($destination));
        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('ledger_lines', 4);
    }

    #[Test]
    public function an_unauthorized_user_cannot_post_and_state_remains_unchanged(): void
    {
        $officer = $this->officer();
        [$source, $destination] = $this->accountsOperatedBy($officer);
        $unauthorized = User::factory()->create();

        $response = $this->actingAs($unauthorized)->post(route('accounts.transfer'), [
            'source_account_id' => $source,
            'destination_account_id' => $destination,
            'currency' => 'COP',
            'amount' => '25.00',
            'request_key' => 'route-unauthorized-1',
        ]);

        $response->assertForbidden();
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame('0.00', $this->balance($destination));
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    #[Test]
    public function an_authorized_officer_cannot_post_an_out_of_scope_account(): void
    {
        $officer = $this->officer();
        $otherOfficer = $this->officer();
        [$source] = $this->accountsOperatedBy($officer);
        [, $outOfScope] = $this->accountsOperatedBy($otherOfficer);

        $response = $this->actingAs($officer)->post(route('accounts.transfer'), [
            'source_account_id' => $source,
            'destination_account_id' => $outOfScope,
            'currency' => 'COP',
            'amount' => '25.00',
            'request_key' => 'route-out-of-scope-1',
        ]);

        $response->assertRedirect()->assertSessionHasErrors('operation');
        $this->assertSame('100.00', $this->balance($source));
        $this->assertSame('0.00', $this->balance($outOfScope));
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    private function officer(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'officer-'.uniqid()]);
        $permission = Permission::firstOrCreate(['name' => 'manage-accounts']);
        $viewPermission = Permission::firstOrCreate(['name' => 'view-accounts']);
        $role->permissions()->attach([$permission->id, $viewPermission->id]);
        $user->roles()->attach($role);

        return $user;
    }

    /** @return array{int, int} */
    private function accountsOperatedBy(User $officer): array
    {
        $customer = Cliente::factory()->create();
        $otherCustomer = Cliente::factory()->create();
        $create = static fn (int $customerId, string $balance): int => DB::table('accounts')->insertGetId([
            'saldo' => $balance,
            'moneda' => 'COP',
            'estado' => 'activa',
            'tipo' => 'savings',
            'familia' => 'personal',
            'customer_id' => $customerId,
            'operado_por' => $officer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$create($customer->id, '100.00'), $create($otherCustomer->id, '0.00')];
    }

    private function balance(int $accountId): string
    {
        return bcadd((string) DB::table('accounts')->where('id', $accountId)->value('saldo'), '0', 2);
    }
}
