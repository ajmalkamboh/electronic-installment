<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('collection_officer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by_id')->constrained('users')->cascadeOnDelete();

            $table->date('assigned_date');
            $table->enum('status', ['active', 'completed', 'reassigned'])->default('active')->index();
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'collection_officer_id', 'status'], 'col_asgn_officer_status_idx');
            $table->index(['company_id', 'installment_agreement_id'], 'col_asgn_agreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_assignments');
    }
};
