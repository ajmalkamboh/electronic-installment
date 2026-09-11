<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: customer_credit_profiles (Underwriting History & Risk Score)
     */
    public function up(): void
    {
        Schema::create('customer_credit_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedInteger('credit_score')->default(50); // 0 to 100
            $table->decimal('max_authorized_credit', 12, 2)->default(150000.00);
            $table->unsignedInteger('active_agreements_count')->default(0);
            $table->unsignedInteger('completed_agreements_count')->default(0);
            $table->unsignedInteger('total_dpd_days')->default(0);
            $table->text('blacklisted_reason')->nullable();
            $table->dateTime('blacklisted_at')->nullable();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index('credit_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_credit_profiles');
    }
};
