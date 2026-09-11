<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: installment_plans (Financing Tenure & Markup Model Templates)
     */
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->unsignedInteger('tenure_months')->default(12); // 3, 6, 9, 12, 18, 24
            $table->enum('markup_calculation_model', ['flat_percentage', 'fixed_amount', 'reducing_balance'])->default('flat_percentage');
            $table->decimal('default_markup_rate_pct', 5, 2)->default(25.00); // e.g. 25.00% annual
            $table->decimal('fixed_markup_amount', 12, 2)->nullable(); // For fixed amount model
            $table->decimal('min_down_payment_pct', 5, 2)->default(20.00); // e.g. 20.00% minimum down payment
            $table->enum('installment_frequency', ['monthly', 'bi_weekly', 'weekly'])->default('monthly');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'tenure_months']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
    }
};
