<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: customer_verifications (Field Investigation Audits)
     */
    public function up(): void
    {
        Schema::create('customer_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('verified_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('verification_type', 30)->default('field_visit'); // field_visit, telephonic, utility_bill
            $table->boolean('residence_confirmed')->default(true);
            $table->boolean('workplace_confirmed')->nullable();
            $table->text('investigator_notes')->nullable();
            $table->string('outcome', 20)->default('approved'); // approved, conditional, rejected
            $table->dateTime('verified_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_verifications');
    }
};
