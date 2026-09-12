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
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('grace_period_days')->default(5)->after('terms_conditions');
            $table->enum('late_fee_type', ['fixed', 'percentage', 'daily_penalty'])->default('fixed')->after('grace_period_days');
            $table->decimal('late_fee_amount', 10, 2)->default(500.00)->after('late_fee_type');
            $table->decimal('max_penalty_cap', 10, 2)->default(2500.00)->after('late_fee_amount');
        });

        Schema::table('installment_plans', function (Blueprint $table) {
            $table->unsignedInteger('grace_period_days')->nullable()->after('min_down_payment_pct');
            $table->enum('late_fee_type', ['fixed', 'percentage', 'daily_penalty'])->nullable()->after('grace_period_days');
            $table->decimal('late_fee_amount', 10, 2)->nullable()->after('late_fee_type');
            $table->decimal('max_penalty_cap', 10, 2)->nullable()->after('late_fee_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->dropColumn(['grace_period_days', 'late_fee_type', 'late_fee_amount', 'max_penalty_cap']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['grace_period_days', 'late_fee_type', 'late_fee_amount', 'max_penalty_cap']);
        });
    }
};
