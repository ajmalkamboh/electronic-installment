<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('serialized_item_id')->nullable()->constrained('serialized_items')->nullOnDelete();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disbursed_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->char('ulid', 26)->unique();
            $table->string('account_number', 50)->unique();
            $table->enum('status', [
                'draft',
                'under_review',
                'approved',
                'active',
                'completed',
                'defaulted',
                'cancelled',
            ])->default('draft')->index();

            // Financial Snapshot locked at agreement creation
            $table->decimal('cash_price', 12, 2);
            $table->decimal('down_payment_amount', 12, 2);
            $table->decimal('down_payment_paid', 12, 2)->default(0.00);
            $table->string('down_payment_receipt_ref')->nullable();
            $table->string('down_payment_method')->nullable();
            $table->decimal('financed_principal', 12, 2);
            $table->decimal('markup_rate_pct', 5, 2);
            $table->decimal('markup_amount', 12, 2);
            $table->decimal('total_financed', 12, 2);
            $table->decimal('total_payable', 12, 2);
            $table->decimal('installment_amount', 12, 2);
            $table->unsignedSmallInteger('tenure_months');
            $table->enum('installment_frequency', ['monthly', 'bi_weekly', 'weekly'])->default('monthly');
            $table->unsignedSmallInteger('total_installments');
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->decimal('remaining_balance', 12, 2);

            // Date Milestones
            $table->date('start_date');
            $table->date('first_due_date');
            $table->date('maturity_date');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Audit & Documentation
            $table->text('approval_notes')->nullable();
            $table->text('handover_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('terms_conditions_snapshot')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_agreements');
    }
};
