<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('borrower_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('destination_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $table->string('product_id');
            $table->string('product_name');
            $table->string('currency', 3);
            $table->decimal('annual_rate', 8, 4);
            $table->unsignedInteger('term_months');
            $table->decimal('principal', 18, 2);
            $table->string('request_key', 191)->unique();
            $table->string('status', 20);
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('transactions')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
