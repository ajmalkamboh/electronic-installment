<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();

            $table->unsignedSmallInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('markup_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('remaining_balance', 12, 2);
            $table->decimal('late_fee_amount', 12, 2)->default(0.00);
            $table->enum('status', [
                'pending',
                'due',
                'partially_paid',
                'paid',
                'overdue',
            ])->default('pending')->index();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['installment_agreement_id', 'installment_number'], 'inst_sched_agr_num_unique');
            $table->index(['company_id', 'installment_agreement_id', 'due_date'], 'inst_sched_comp_agr_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_schedules');
    }
};
