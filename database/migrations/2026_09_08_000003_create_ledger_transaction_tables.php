<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 30);
            $table->string('currency', 3);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('source_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('destination_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('reversal_of_transaction_id')->nullable()->constrained('transactions')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('ledger_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('request_key', 191)->unique();
            $table->string('payload_fingerprint', 64);
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('ledger_lines');
        Schema::dropIfExists('transactions');
        Schema::enableForeignKeyConstraints();
    }
};
