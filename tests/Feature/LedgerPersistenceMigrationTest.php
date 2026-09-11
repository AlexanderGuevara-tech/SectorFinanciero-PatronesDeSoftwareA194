<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LedgerPersistenceMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_transaction_ledger_and_idempotency_tables_with_required_constraints(): void
    {
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('transactions'));
        $this->assertTrue(Schema::hasColumns('transactions', [
            'id', 'kind', 'currency', 'actor_id', 'source_account_id',
            'destination_account_id', 'reversal_of_transaction_id', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasTable('ledger_lines'));
        $this->assertTrue(Schema::hasColumns('ledger_lines', [
            'id', 'transaction_id', 'account_id', 'amount', 'currency', 'actor_id', 'created_at', 'updated_at',
        ]));
        $this->assertTrue(Schema::hasTable('idempotency_keys'));
        $this->assertTrue(Schema::hasColumns('idempotency_keys', [
            'id', 'request_key', 'payload_fingerprint', 'transaction_id', 'created_at', 'updated_at',
        ]));

        $this->assertNotContains(Schema::getColumnType('ledger_lines', 'amount'), ['float', 'double']);
    }
}
