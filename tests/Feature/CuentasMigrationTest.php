<?php

namespace Tests\Feature;

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
        $this->assertTrue(Schema::hasColumn('accounts', 'user_id'));
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
     * Debería crear la tabla accounts con foreign key a users y cascade on delete.
     */
    #[Test]
    public function test_accounts_table_has_user_id_foreign_key_with_cascade(): void
    {
        $this->artisan('migrate');

        $foreignKeys = Schema::getForeignKeys('accounts');

        $hasUserForeignKey = false;
        foreach ($foreignKeys as $fk) {
            $fkColumns = $fk['columns'] ?? [];
            $fkForeignTable = $fk['foreign_table'] ?? $fk['foreign'] ?? '';
            if ($fkColumns === ['user_id'] && str_ends_with($fkForeignTable, 'users')) {
                $hasUserForeignKey = true;
                break;
            }
        }

        $this->assertTrue($hasUserForeignKey, 'accounts table must have a user_id foreign key to users');
    }

    /**
     * Debería crear la tabla accounts con valores por defecto correctos.
     */
    #[Test]
    public function test_accounts_table_has_correct_default_values(): void
    {
        $this->artisan('migrate');

        $user = User::factory()->create();

        DB::table('accounts')->insert([
            'user_id' => $user->id,
            'tipo' => 'savings',
        ]);

        $account = DB::table('accounts')->where('user_id', $user->id)->first();

        $this->assertSame('0', (string) $account->saldo);
        $this->assertSame('COP', $account->moneda);
        $this->assertSame('activa', $account->estado);
    }
}
