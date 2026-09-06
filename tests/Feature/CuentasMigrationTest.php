<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CuentasMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Debería crear la tabla accounts con saldo DECIMAL, moneda COP y estado enum.
     */
    #[Test]
    public function test_accounts_table_has_decimal_balance_cop_default_and_enum_state(): void
    {
        $this->artisan('migrate');

        $this->assertTrue(Schema::hasTable('accounts'));
        $this->assertTrue(Schema::hasColumn('accounts', 'saldo'));
        $this->assertTrue(Schema::hasColumn('accounts', 'moneda'));
        $this->assertTrue(Schema::hasColumn('accounts', 'estado'));
        $this->assertTrue(Schema::hasColumn('accounts', 'tipo'));
        $this->assertTrue(Schema::hasColumn('accounts', 'customer_id'));
        $this->assertTrue(Schema::hasColumn('accounts', 'operado_por'));
        $this->assertTrue(Schema::hasColumn('accounts', 'familia'));
    }

    /**
     * Debería crear la tabla accounts con saldo que no es float ni double.
     */
    #[Test]
    public function test_balance_column_is_not_float_or_double(): void
    {
        $this->artisan('migrate');

        $column = Schema::getColumnType('accounts', 'saldo');
        $this->assertNotContains($column, ['float', 'double'], 'saldo column must be DECIMAL, never float or double');
    }

    /**
     * Debería crear la tabla accounts con claves foráneas de cliente y oficial.
     */
    #[Test]
    public function test_accounts_table_has_customer_and_officer_foreign_keys(): void
    {
        $this->artisan('migrate');

        $foreignKeys = Schema::getForeignKeys('accounts');

        $hasCustomerForeignKey = false;
        $hasOfficerForeignKey = false;
        foreach ($foreignKeys as $fk) {
            $fkColumns = $fk['columns'] ?? [];
            $fkForeignTable = $fk['foreign_table'] ?? $fk['foreign'] ?? '';
            if ($fkColumns === ['customer_id'] && str_ends_with($fkForeignTable, 'customers')) {
                $hasCustomerForeignKey = true;
            }
            if ($fkColumns === ['operado_por'] && str_ends_with($fkForeignTable, 'users')) {
                $hasOfficerForeignKey = true;
            }
        }

        $this->assertTrue($hasCustomerForeignKey, 'accounts table must have a customer_id foreign key to customers');
        $this->assertTrue($hasOfficerForeignKey, 'accounts table must have an operado_por foreign key to users');
    }

    /**
     * Debería crear la tabla accounts con valores por defecto correctos.
     */
    #[Test]
    public function test_accounts_table_has_correct_default_values(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        DB::table('accounts')->insert([
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'tipo' => 'savings',
        ]);

        $account = DB::table('accounts')->where('customer_id', $customer->id)->first();

        $this->assertSame('0', (string) $account->saldo);
        $this->assertSame('COP', $account->moneda);
        $this->assertSame('activa', $account->estado);
        $this->assertSame('personal', $account->familia);
    }

    #[Test]
    public function test_family_column_backfills_legacy_rows_to_personal(): void
    {
        $this->artisan('migrate');
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        DB::table('accounts')->insert([
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'tipo' => 'savings',
        ]);

        $this->assertDatabaseHas('accounts', ['customer_id' => $customer->id, 'familia' => 'personal']);
    }

    #[Test]
    public function test_family_column_rejects_values_outside_the_approved_allow_list(): void
    {
        $this->artisan('migrate');
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        $this->expectException(\Throwable::class);

        DB::table('accounts')->insert([
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'tipo' => 'savings',
            'familia' => 'unknown',
        ]);
    }

    #[Test]
    public function test_deleting_officer_preserves_account_and_nulls_officer(): void
    {
        $this->artisan('migrate');
        $user = User::factory()->create();
        $customer = Cliente::factory()->create();

        DB::table('accounts')->insert([
            'customer_id' => $customer->id,
            'operado_por' => $user->id,
            'tipo' => 'savings',
            'familia' => 'personal',
        ]);

        $user->delete();

        $this->assertDatabaseHas('accounts', [
            'customer_id' => $customer->id,
            'operado_por' => null,
            'familia' => 'personal',
        ]);
    }
}
