<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: guarantors (Legal Guarantors)
     */
    public function up(): void
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->ulid('ulid')->unique();
            $table->string('cnic', 20)->index();
            $table->string('full_name', 191);
            $table->string('relationship', 50); // brother, father, colleague, friend, uncle, etc.
            $table->string('occupation', 100)->nullable();
            $table->string('employer_name', 191)->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('mobile', 25);
            $table->text('address');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'cnic']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};
