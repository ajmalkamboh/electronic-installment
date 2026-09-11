<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained('users')->nullOnDelete();

            $table->char('ulid', 26)->unique();
            $table->string('payment_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'raast',
                'easypaisa',
                'jazzcash',
                'cheque',
            ])->default('cash');
            $table->string('reference_number', 100)->nullable();
            $table->date('payment_date');
            $table->enum('status', [
                'submitted',
                'acknowledged',
                'rejected',
                'reversed',
            ])->default('acknowledged')->index();

            // Financial Allocation Breakdown
            $table->decimal('late_fee_paid', 12, 2)->default(0.00);
            $table->decimal('principal_paid', 12, 2)->default(0.00);
            $table->decimal('markup_paid', 12, 2)->default(0.00);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'payment_date']);
            $table->index(['company_id', 'installment_agreement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
