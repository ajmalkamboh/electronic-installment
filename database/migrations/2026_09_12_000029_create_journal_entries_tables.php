<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tables: journal_entries, journal_entry_items (Double-entry general ledger)
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date')->index();
            $table->string('reference_type', 60)->index(); // down_payment, disbursement, installment_payment, late_fee_accrual, late_fee_waiver, early_settlement, repossession, write_off, manual_journal
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->text('description');
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'posted', 'void'])->default('posted')->index();
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_credit', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'entry_date']);
        });

        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('debit', 15, 2)->default(0.00);
            $table->decimal('credit', 15, 2)->default(0.00);
            $table->string('memo', 255)->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
    }
};
