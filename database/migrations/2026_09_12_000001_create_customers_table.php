<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: customers (Debtor Entity)
     * Ownership: Company (tenant scoped).
     * CNIC: 13-digit Pakistani format (XXXXX-XXXXXXX-X), unique per company.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->ulid('ulid')->unique();
            $table->string('cnic', 20)->index();
            $table->string('full_name', 191);
            $table->string('father_or_husband_name', 191)->nullable();
            $table->string('gender', 10)->default('male');
            $table->string('mobile_primary', 25);
            $table->string('mobile_secondary', 25)->nullable();
            $table->string('whatsapp_number', 25)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('present_address');
            $table->text('permanent_address')->nullable();
            $table->string('residence_type', 20)->default('owned'); // owned, rented, family
            $table->unsignedInteger('residence_tenure_years')->nullable();
            $table->decimal('monthly_household_income', 12, 2)->nullable();
            $table->string('utility_bill_ref_number', 50)->nullable();
            $table->string('status', 20)->default('pending_verification')->index(); // pending_verification, active, restricted, blacklisted
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'cnic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
