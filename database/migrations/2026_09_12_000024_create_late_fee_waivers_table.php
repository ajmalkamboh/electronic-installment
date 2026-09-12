<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: late_fee_waivers (Immutable Supervisory Waiver Audit Trail)
     */
    public function up(): void
    {
        Schema::create('late_fee_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->constrained('installment_schedules')->cascadeOnDelete();
            $table->foreignId('waived_by_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('original_late_fee', 10, 2);
            $table->decimal('waived_amount', 10, 2);
            $table->decimal('remaining_late_fee', 10, 2);
            $table->text('reason'); // Mandatory justification
            $table->timestamps();

            $table->index(['company_id', 'installment_agreement_id']);
            $table->index(['company_id', 'installment_schedule_id']);
            $table->index(['company_id', 'waived_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_fee_waivers');
    }
};
