<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: recovery_cases (Delinquency & Repossession Case Docket)
     */
    public function up(): void
    {
        Schema::create('recovery_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('case_number', 50);
            $table->enum('stage', [
                'grace_period',
                'overdue_reminder',
                'tele_collection',
                'field_recovery',
                'legal_notice',
                'repossession_pending',
                'repossessed',
                'written_off',
                'resolved',
            ])->default('grace_period');
            $table->enum('status', [
                'open',
                'in_progress',
                'escalated',
                'repossessed',
                'written_off',
                'settled',
                'closed',
            ])->default('open');
            $table->unsignedInteger('days_past_due')->default(0);
            $table->decimal('total_overdue_amount', 12, 2)->default(0.00);
            $table->decimal('total_late_fees', 10, 2)->default(0.00);
            $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('escalated_by_id')->nullable()->constrained('users')->nullOnDelete();

            // Legal & Warning notices milestones
            $table->dateTime('warning_notice_at')->nullable();
            $table->string('warning_notice_ref', 50)->nullable();
            $table->dateTime('legal_notice_at')->nullable();
            $table->string('legal_notice_ref', 50)->nullable();

            // Repossession milestones
            $table->dateTime('repossession_authorized_at')->nullable();
            $table->foreignId('repossession_authorized_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('repossessed_at')->nullable();
            $table->foreignId('repossessed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('repossessed_condition', 50)->nullable(); // like_new, good, fair, damaged, scrapped
            $table->text('repossession_notes')->nullable();

            // Bad-debt write-off milestones
            $table->dateTime('written_off_at')->nullable();
            $table->foreignId('written_off_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('written_off_amount', 12, 2)->nullable();
            $table->text('written_off_reason')->nullable();

            // Closure / Settlement
            $table->dateTime('settled_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'case_number']);
            $table->index(['company_id', 'stage']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'assigned_officer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_cases');
    }
};
