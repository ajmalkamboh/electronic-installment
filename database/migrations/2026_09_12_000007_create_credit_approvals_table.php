<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: credit_approvals (Managerial Decisions & Authorized Limits)
     */
    public function up(): void
    {
        Schema::create('credit_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('credit_assessment_id')->constrained('credit_assessments')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('approval_level', 40)->default('level_1_branch_manager'); // level_1_branch_manager, level_2_company_admin
            $table->enum('decision', ['approved', 'conditional', 'rejected']);
            $table->decimal('authorized_credit_limit', 12, 2)->default(0.00);
            $table->text('conditions_imposed')->nullable();
            $table->text('approval_notes')->nullable();
            $table->dateTime('decided_at');

            $table->timestamps();

            $table->index(['company_id', 'decision']);
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_approvals');
    }
};
