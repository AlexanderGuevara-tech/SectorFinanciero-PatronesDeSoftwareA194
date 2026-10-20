<?php

namespace Tests\Feature;

use App\Application\Loan\ServicioPrestamos;
use App\Infrastructure\Persistence\Cliente;
use App\Infrastructure\Persistence\RepositorioPrestamosEloquent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PrestamosTest extends TestCase
{
    use DatabaseMigrations;

    public function test_approved_loan_disburses_once_from_real_funding_with_balanced_distinct_ledger(): void
    {
        [$applicant, $officer, $borrower, $destination, $funding] = $this->accounts('100.00');
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'application-1');
        $same = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'application-1');
        $this->assertSame($loan->id, $same->id);
        $this->assertSame($loan->producto, $same->producto);

        $service->evaluar($loan->id, $officer, true);
        $posted = $service->desembolsar($loan->id, $officer);
        $retry = $service->desembolsar($loan->id, $officer);

        $this->assertSame($posted->transactionId, $retry->transactionId);
        $this->assertSame('75.00', $this->balance($funding));
        $this->assertSame('25.00', $this->balance($destination));
        $this->assertDatabaseHas('transactions', ['id' => $posted->transactionId, 'kind' => 'loan_disbursement', 'source_account_id' => $funding, 'destination_account_id' => $destination]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'disbursed', 'transaction_id' => $posted->transactionId, 'principal' => '25.00']);
        $this->assertSame(['-25.00', '25.00'], DB::table('ledger_lines')->orderBy('id')->pluck('amount')->map(fn ($value): string => bcadd((string) $value, '0', 2))->all());
        $this->assertSame(1, DB::table('idempotency_keys')->count());
        $this->assertSame(1, DB::table('transactions')->count());
    }

    public function test_configured_ordinary_customer_account_cannot_fund_a_loan_or_mutate_financial_records(): void
    {
        [$applicant, $officer, $borrower, $destination, $funding] = $this->accounts('100.00', false);
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'ordinary-funding');
        $service->evaluar($loan->id, $officer, true);

        $rejected = false;
        try {
            $service->desembolsar($loan->id, $officer);
        } catch (\RuntimeException) {
            $rejected = true;
        }

        $this->assertTrue($rejected, 'An ordinary customer account funded the loan.');
        $this->assertSame('100.00', $this->balance($funding));
        $this->assertSame('0.00', $this->balance($destination));
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'approved', 'transaction_id' => null]);
        $this->assertSame(0, DB::table('transactions')->count());
        $this->assertSame(0, DB::table('ledger_lines')->count());
        $this->assertSame(0, DB::table('idempotency_keys')->count());
    }

    public function test_rejects_foreign_account_and_conflicting_application_key_without_mutation(): void
    {
        [$applicant, , $borrower, $destination] = $this->accounts();
        $service = app(ServicioPrestamos::class);
        $foreign = Cliente::factory()->create();
        $foreignAccount = $this->account($foreign->id, '0.00', $applicant);

        try {
            $service->solicitar($applicant, $borrower, $foreignAccount, 'academic', '25.00', 'foreign');
            $this->fail('Foreign destination accepted.');
        } catch (\InvalidArgumentException) {
            $this->assertSame(0, DB::table('loans')->count());
        }
        $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'same-key');
        $this->expectException(\InvalidArgumentException::class);
        $service->solicitar($applicant, $borrower, $destination, 'academic', '26.00', 'same-key');
    }

    public function test_self_approval_unauthorized_officer_and_rejected_state_cannot_disburse(): void
    {
        [$applicant, $officer, $borrower, $destination] = $this->accounts();
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'request-2');
        foreach ([$applicant, User::factory()->create()->id] as $actor) {
            try {
                $service->evaluar($loan->id, $actor, true);
                $this->fail('Unauthorized approval accepted.');
            } catch (\RuntimeException|AuthorizationException) {
                $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'pending']);
            }
        }
        $service->evaluar($loan->id, $officer, false);
        $this->expectException(\RuntimeException::class);
        $service->desembolsar($loan->id, $officer);
    }

    public function test_insufficient_real_funding_or_missing_configuration_never_posts(): void
    {
        [$applicant, $officer, $borrower, $destination, $funding] = $this->accounts('10.00');
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'request-3');
        $service->evaluar($loan->id, $officer, true);
        foreach ([$funding, null] as $configured) {
            config()->set('loans.funding_account_id', $configured);
            try {
                $service->desembolsar($loan->id, $officer);
                $this->fail('Unfunded loan disbursed.');
            } catch (\RuntimeException) {
                $this->assertSame('10.00', $this->balance($funding));
                $this->assertSame('0.00', $this->balance($destination));
                $this->assertSame(0, DB::table('transactions')->count());
                $this->assertSame(0, DB::table('ledger_lines')->count());
            }
        }
    }

    public function test_ledger_failure_rolls_back_both_balances_transaction_and_loan_state(): void
    {
        [$applicant, $officer, $borrower, $destination, $funding] = $this->accounts('100.00');
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'request-4');
        $service->evaluar($loan->id, $officer, true);
        DB::connection()->getSchemaBuilder()->drop('ledger_lines');

        try {
            $service->desembolsar($loan->id, $officer);
            $this->fail('Failed ledger write did not roll back.');
        } catch (QueryException) {
            $this->assertSame('100.00', $this->balance($funding));
            $this->assertSame('0.00', $this->balance($destination));
            $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'approved', 'transaction_id' => null]);
            $this->assertSame(0, DB::table('transactions')->count());
            $this->assertSame(0, DB::table('idempotency_keys')->count());
        }
    }

    public function test_two_loans_share_only_product_definition_and_retain_historical_terms(): void
    {
        [$applicant, $officer, $borrower, $destination] = $this->accounts();
        $service = app(ServicioPrestamos::class);
        $otherBorrower = Cliente::factory()->create();
        $otherDestination = $this->account($otherBorrower->id, '0.00', $applicant);
        $first = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'first-loan');
        $second = $service->solicitar($applicant, $otherBorrower->id, $otherDestination, 'academic', '30.00', 'second-loan');
        $service->evaluar($first->id, $officer, true);

        $this->assertSame($first->producto, $second->producto);
        $this->assertNotSame($first->borrowerId, $second->borrowerId);
        $this->assertNotSame($first->destinationAccountId, $second->destinationAccountId);
        $this->assertSame('25.00', $first->principal);
        $this->assertSame('30.00', $second->principal);
        $this->assertDatabaseHas('loans', ['id' => $first->id, 'status' => 'approved']);
        $this->assertDatabaseHas('loans', ['id' => $second->id, 'status' => 'pending']);
        config()->set('loans.products', []);
        $persisted = app(RepositorioPrestamosEloquent::class)->porIdBloqueado($first->id);
        $this->assertSame('0.0500', $persisted->producto->annualRate);
        $this->assertSame(12, $persisted->producto->termMonths);
    }

    public function test_pending_loan_and_changed_destination_owner_never_receive_funds(): void
    {
        [$applicant, $officer, $borrower, $destination] = $this->accounts();
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'owner-change');
        try {
            $service->desembolsar($loan->id, $officer);
            $this->fail('Pending loan was posted.');
        } catch (\RuntimeException) {
            $this->assertSame(0, DB::table('transactions')->count());
        }
        $service->evaluar($loan->id, $officer, true);
        DB::table('accounts')->where('id', $destination)->update(['customer_id' => Cliente::factory()->create()->id]);

        $this->expectException(\RuntimeException::class);
        $service->desembolsar($loan->id, $officer);
    }

    public function test_unauthorized_disbursement_cannot_claim_idempotent_retry(): void
    {
        [$applicant, $officer, $borrower, $destination] = $this->accounts();
        $service = app(ServicioPrestamos::class);
        $loan = $service->solicitar($applicant, $borrower, $destination, 'academic', '25.00', 'retry-authorization');
        $service->evaluar($loan->id, $officer, true);
        $service->desembolsar($loan->id, $officer);
        $this->expectException(AuthorizationException::class);
        $service->desembolsar($loan->id, User::factory()->create()->id);
    }

    /** @return array{int, int, int, int, int} */
    private function accounts(string $fundingBalance = '100.00', bool $internalFunding = true): array
    {
        config()->set('loans.products', ['academic' => ['name' => 'Academic example', 'currency' => 'COP', 'annual_rate' => '0.0500', 'term_months' => 12]]);
        $permission = Permission::create(['name' => 'manage-accounts']);
        $role = Role::create(['name' => 'loan-officer']);
        $role->permissions()->attach($permission);
        $applicant = User::factory()->create();
        $officer = User::factory()->create();
        $applicant->roles()->attach($role);
        $officer->roles()->attach($role);
        $borrower = Cliente::factory()->create();
        $fundingOwner = Cliente::factory()->create();
        $destination = $this->account($borrower->id, '0.00', $applicant->id);
        $funding = $this->account($fundingOwner->id, $fundingBalance, $officer->id, $internalFunding);
        config()->set('loans.funding_account_id', $funding);

        return [$applicant->id, $officer->id, $borrower->id, $destination, $funding];
    }

    private function account(int $customerId, string $balance, int $operatorId, bool $isInternal = false): int
    {
        return DB::table('accounts')->insertGetId([
            'saldo' => $balance, 'moneda' => 'COP', 'estado' => 'activa', 'tipo' => 'savings', 'familia' => 'personal',
            'customer_id' => $customerId, 'operado_por' => $operatorId, 'es_interna' => $isInternal, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function balance(int $accountId): string
    {
        return bcadd((string) DB::table('accounts')->where('id', $accountId)->value('saldo'), '0', 2);
    }
}
