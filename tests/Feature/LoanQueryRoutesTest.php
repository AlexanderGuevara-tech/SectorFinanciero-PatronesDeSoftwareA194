<?php

namespace Tests\Feature;

use App\Application\Loan\ConsultaPrestamo;
use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanQueryRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_visitor_is_redirected_to_login(): void
    {
        $this->get('/loans/1')->assertRedirect(route('login'));
    }

    public function test_linked_borrower_can_view_own_loan_without_permission(): void
    {
        $customer = Cliente::factory()->create();
        $borrower = User::factory()->create(['customer_id' => $customer->id]);
        $loanId = $this->loan($customer->id);

        $this->actingAs($borrower)->get('/loans/'.$loanId)
            ->assertOk()
            ->assertViewIs('prestamos.show')
            ->assertSee('Préstamo #'.$loanId)
            ->assertSee('25.00')
            ->assertSee('Academic example');
    }

    public function test_borrower_receives_404_for_another_customers_loan(): void
    {
        $borrowerCustomer = Cliente::factory()->create();
        $foreignCustomer = Cliente::factory()->create();
        $borrower = User::factory()->create(['customer_id' => $borrowerCustomer->id]);
        $loanId = $this->loan($foreignCustomer->id);

        $this->actingAs($borrower)->get('/loans/'.$loanId)->assertNotFound();
    }

    public function test_operator_with_permission_and_customer_scope_can_view_loan(): void
    {
        $customer = Cliente::factory()->create();
        $operator = User::factory()->create();
        $this->grantPermission($operator);
        $loanId = $this->loan($customer->id, $operator->id);

        $this->actingAs($operator)->get('/loans/'.$loanId)
            ->assertOk()
            ->assertSee('Préstamo #'.$loanId);
    }

    public function test_operator_with_permission_outside_customer_scope_receives_404(): void
    {
        $customer = Cliente::factory()->create();
        $operator = User::factory()->create();
        $this->grantPermission($operator);
        $loanId = $this->loan($customer->id);

        $this->actingAs($operator)->get('/loans/'.$loanId)->assertNotFound();
    }

    public function test_in_scope_operator_without_permission_receives_404(): void
    {
        $customer = Cliente::factory()->create();
        $operator = User::factory()->create();
        $loanId = $this->loan($customer->id, $operator->id);

        $this->actingAs($operator)->get('/loans/'.$loanId)->assertNotFound();
    }

    public function test_missing_loan_returns_404(): void
    {
        $customer = Cliente::factory()->create();
        $borrower = User::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($borrower)->get('/loans/999999')->assertNotFound();
    }

    public function test_route_uses_injected_query_contract_and_maps_null_to_404(): void
    {
        $actor = User::factory()->create();
        $query = $this->mock(ConsultaPrestamo::class);
        $query->shouldReceive('porId')->once()->with($actor, 999999)->andReturn(null);

        $this->actingAs($actor)->get('/loans/999999')->assertNotFound();
    }

    private function grantPermission(User $user): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view-loans']);
        $role = Role::create(['name' => 'loan-reader']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
    }

    private function loan(int $borrowerId, ?int $operatorId = null): int
    {
        $applicant = User::factory()->create();
        $accountId = DB::table('accounts')->insertGetId([
            'saldo' => '0.00', 'moneda' => 'COP', 'estado' => 'activa', 'tipo' => 'savings',
            'familia' => 'personal', 'customer_id' => $borrowerId, 'operado_por' => $operatorId ?? $applicant->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('loans')->insertGetId([
            'borrower_id' => $borrowerId, 'destination_account_id' => $accountId, 'applicant_id' => $applicant->id,
            'product_id' => 'academic', 'product_name' => 'Academic example', 'currency' => 'COP',
            'annual_rate' => '0.0500', 'term_months' => 12, 'principal' => '25.00',
            'request_key' => 'loan-'.$accountId, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
