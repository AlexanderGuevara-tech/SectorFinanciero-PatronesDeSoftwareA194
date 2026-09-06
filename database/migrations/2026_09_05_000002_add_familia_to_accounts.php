<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->string('familia', 20)
                ->nullable()
                ->default('personal')
                ->after('tipo');
        });

        DB::table('accounts')->whereNull('familia')->update(['familia' => 'personal']);

        Schema::table('accounts', function (Blueprint $table): void {
            $table->string('familia', 20)->default('personal')->nullable(false)->change();
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE TRIGGER accounts_familia_insert_allow_list BEFORE INSERT ON accounts
                WHEN NEW.familia NOT IN ('personal', 'empresarial')
                BEGIN SELECT RAISE(ABORT, 'Invalid account family'); END");
            DB::statement("CREATE TRIGGER accounts_familia_update_allow_list BEFORE UPDATE OF familia ON accounts
                WHEN NEW.familia NOT IN ('personal', 'empresarial')
                BEGIN SELECT RAISE(ABORT, 'Invalid account family'); END");
        } else {
            DB::statement("ALTER TABLE accounts ADD CONSTRAINT accounts_familia_allow_list CHECK (familia IN ('personal', 'empresarial'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS accounts_familia_insert_allow_list');
            DB::statement('DROP TRIGGER IF EXISTS accounts_familia_update_allow_list');
        } elseif (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE accounts DROP CHECK accounts_familia_allow_list');
        } else {
            DB::statement('ALTER TABLE accounts DROP CONSTRAINT accounts_familia_allow_list');
        }

        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('familia');
        });
    }
};
