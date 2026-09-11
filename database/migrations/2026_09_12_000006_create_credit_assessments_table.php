<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: credit_assessments (Quantitative Underwriting & DTI Analysis)
     */
    public function up(): void
    {
        Schema::create('credit_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('assessed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->char('ulid', 26)->unique();

            // Financial & Underwriting Metrics
            $table->decimal('monthly_income', 12, 2);
            $table->decimal('existing_debt_obligations', 12, 2)->default(0.00);
            $table->decimal('proposed_installment_limit', 12, 2);
            $table->decimal('calculated_dti_percentage', 5, 2); // e.g. 34.50%
            $table->unsignedInteger('score')->default(50); // 0 to 100
            $table->enum('risk_tier', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Credit Officer Recommendation
            $table->decimal('recommended_limit', 12, 2);
            $table->enum('recommendation', ['approved', 'conditional', 'rejected'])->default('approved');
            $table->text('conditions_summary')->nullable();
            $table->text('assessment_notes')->nullable();

            // Lifecycle Status
            $table->enum('status', ['pending_approval', 'approved', 'rejected', 'superseded'])->default('pending_approval');
            $table->dateTime('assessed_at');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('risk_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_assessments');
    }
};
