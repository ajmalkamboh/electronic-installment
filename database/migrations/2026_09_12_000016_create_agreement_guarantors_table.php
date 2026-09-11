<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('guarantor_id')->constrained('guarantors')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->string('relationship', 50)->nullable();
            $table->string('verification_status', 30)->default('verified');
            $table->timestamps();

            $table->unique(['installment_agreement_id', 'guarantor_id'], 'agr_guar_agreement_guarantor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_guarantors');
    }
};
