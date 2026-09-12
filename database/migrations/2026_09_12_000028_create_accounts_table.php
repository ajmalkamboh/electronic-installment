<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: accounts (Standard Chart of Accounts)
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('code', 20)->index();
            $table->string('name', 191);
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->index();
            $table->string('category', 60)->index(); // cash_and_bank, accounts_receivable, inventory, current_liability, equity, operating_revenue, direct_expense, operating_expense, etc.
            $table->enum('normal_balance', ['debit', 'credit'])->default('debit');
            $table->boolean('is_system')->default(false); // Protected default accounts
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
