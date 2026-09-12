<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('installment_schedules', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'due_date'], 'perf_sched_comp_stat_due_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'payment_date'], 'perf_pay_comp_stat_date_idx');
            $table->index(['installment_agreement_id', 'status'], 'perf_pay_agr_stat_idx');
        });

        Schema::table('serialized_items', function (Blueprint $table) {
            $table->index(['company_id', 'status', 'branch_id'], 'perf_item_comp_stat_branch_idx');
        });

        Schema::table('recovery_cases', function (Blueprint $table) {
            $table->index(['company_id', 'stage', 'status'], 'perf_rec_comp_stage_stat_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installment_schedules', function (Blueprint $table) {
            $table->dropIndex('perf_sched_comp_stat_due_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('perf_pay_comp_stat_date_idx');
            $table->dropIndex('perf_pay_agr_stat_idx');
        });

        Schema::table('serialized_items', function (Blueprint $table) {
            $table->dropIndex('perf_item_comp_stat_branch_idx');
        });

        Schema::table('recovery_cases', function (Blueprint $table) {
            $table->dropIndex('perf_rec_comp_stage_stat_idx');
        });
    }
};
