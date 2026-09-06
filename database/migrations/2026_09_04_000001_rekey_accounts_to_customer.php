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
            $table->dropForeign(['user_id']);
            $table->foreignId('customer_id')->nullable()->after('tipo')->constrained('customers')->restrictOnDelete();
            $table->renameColumn('user_id', 'operado_por');
        });

        Schema::table('accounts', function (Blueprint $table): void {
            $table->foreignId('operado_por')->nullable()->change();
            $table->foreign('operado_por')->references('id')->on('users')->nullOnDelete();
        });

        DB::transaction(function (): void {
            $accountCount = DB::table('accounts')->count();
            $accounts = DB::table('accounts')->whereNull('customer_id')->get();
            foreach ($accounts as $account) {
                $user = DB::table('users')->where('id', $account->operado_por)->first();
                $customerId = DB::table('customers')->insertGetId([
                    'name' => $user?->name ?? 'Cliente migrado',
                    'doc_type' => 'BACKFILL',
                    'doc_number' => 'BACKFILL'.$account->id,
                    'email' => $user?->email,
                    'phone' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('accounts')->where('id', $account->id)->update(['customer_id' => $customerId]);
            }

            if (DB::table('accounts')->count() !== $accountCount) {
                throw new RuntimeException('Account count changed during customer backfill.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['operado_por']);
            $table->dropColumn('customer_id');
            $table->renameColumn('operado_por', 'user_id');
        });

        Schema::table('accounts', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
