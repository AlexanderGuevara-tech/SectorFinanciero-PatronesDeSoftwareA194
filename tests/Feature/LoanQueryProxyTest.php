<?php

namespace Tests\Feature;

use App\Application\Loan\ConsultaPrestamo;
use App\Application\Loan\ProxyConsultaPrestamo;
use App\Application\Loan\ServicioConsultaPrestamo;
use App\Domain\Loan\Prestamo;
use App\Infrastructure\Persistence\Cliente;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanQueryProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_registers_permission_without_granting_it_to_existing_roles(): void
    {
        (new DatabaseSeeder)->run();

        $this->assertDatabaseHas('permissions', ['name' => 'view-loans']);
        $this->assertFalse(User::where('email', 'test@example.com')->firstOrFail()->can('view-loans'));
        $this->assertSame(0, DB::table('permission_role')->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')->where('permissions.name', 'view-loans')->count());
    }

    public function test_linked_borrower_receives_only_own_loan_without_a_role_permission(): void
    {
        $borrower = Cliente::factory()->create();
        $other = Cliente::factory()->create();
        $user = User::factory()->create(['customer_id' => $borrower->id]);
        $ownLoan = $this->loan($borrower->id);
        $foreignLoan = $this->loan($other->id);
        $query = app(ConsultaPrestamo::class);

        $this->assertInstanceOf(ProxyConsultaPrestamo::class, $query);
        $this->assertSame($ownLoan, $query->porId($user, $ownLoan)?->id);
        $this->assertNull($query->porId($user, $foreignLoan));
        $this->assertNull($query->porId($user, 999999));
    }

    public function test_unlinked_user_cannot_infer_borrower_from_matching_user_id_or_email(): void
    {
        $borrower = Cliente::factory()->create(['email' => 'same@example.test']);
        $user = User::factory()->create(['email' => 'same@example.test']);
        $loan = $this->loan($borrower->id);

        $this->assertSame($user->id, $borrower->id);
        $this->assertNull(app(ConsultaPrestamo::class)->porId($user, $loan));
    }

    public function test_operator_requires_both_permission_and_customer_scope(): void
    {
        $borrower = Cliente::factory()->create();
        $other = Cliente::factory()->create();
        $operator = User::factory()->create();
        $loan = $this->loan($borrower->id, $operator->id);
        $otherLoan = $this->loan($other->id);
        $query = app(ConsultaPrestamo::class);

        $this->assertNull($query->porId($operator, $loan));
        $this->grantPermission($operator);
        $this->assertSame($loan, $query->porId($operator, $loan)?->id);
        $this->assertNull($query->porId($operator, $otherLoan));
    }

    public function test_linked_operator_cannot_use_own_link_to_bypass_operator_scope_for_foreign_loan(): void
    {
        $borrower = Cliente::factory()->create();
        $foreign = Cliente::factory()->create();
        $operator = User::factory()->create(['customer_id' => $borrower->id]);
        $loan = $this->loan($foreign->id);
        $this->grantPermission($operator);

        $this->assertNull(app(ConsultaPrestamo::class)->porId($operator, $loan));
    }

    public function test_proxy_never_calls_subject_before_authorization_and_delegates_once_afterward(): void
    {
        $borrower = Cliente::factory()->create();
        $foreign = Cliente::factory()->create();
        $user = User::factory()->create(['customer_id' => $borrower->id]);
        $ownLoan = $this->loan($borrower->id);
        $foreignLoan = $this->loan($foreign->id);
        $operator = User::factory()->create();
        $scopedLoan = $this->loan($foreign->id, $operator->id);
        $subject = \Mockery::mock(ConsultaPrestamo::class);
        $subject->shouldReceive('porId')->once()->with($user, $ownLoan)->andReturn(app(ServicioConsultaPrestamo::class)->porId($user, $ownLoan));
        $proxy = app()->make(ProxyConsultaPrestamo::class, ['subject' => $subject]);

        $this->assertNull($proxy->porId($user, $foreignLoan));
        $this->assertNull($proxy->porId($operator, $scopedLoan));
        $this->assertNull($proxy->porId($user, 999999));
        $this->assertInstanceOf(Prestamo::class, $proxy->porId($user, $ownLoan));
    }

    public function test_real_subject_returns_complete_loan_without_locking_or_mutation(): void
    {
        $borrower = Cliente::factory()->create();
        $user = User::factory()->create(['customer_id' => $borrower->id]);
        $loanId = $this->loan($borrower->id);
        $subject = app(ServicioConsultaPrestamo::class);

        $loan = $subject->porId($user, $loanId);

        $this->assertSame($loanId, $loan?->id);
        $this->assertSame($borrower->id, $loan?->borrowerId);
        $this->assertSame('25.00', $loan?->principal);
        $this->assertSame('0.0500', $loan?->producto->annualRate);
        $this->assertNull($subject->porId($user, 999999));
        $this->assertDatabaseHas('loans', ['id' => $loanId, 'status' => 'pending', 'principal' => '25.00']);
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
