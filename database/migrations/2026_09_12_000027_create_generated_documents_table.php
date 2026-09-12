<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: generated_documents (Audit Log of All Printed & Issued Legal Documents)
     */
    public function up(): void
    {
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('document_number', 50);
            $table->enum('document_type', [
                'application_form',
                'verification_report',
                'credit_approval_sheet',
                'installment_contract',
                'guarantor_affidavit',
                'delivery_note',
                'payment_receipt',
                'account_statement',
                'settlement_letter',
                'clearance_noc',
            ]);
            $table->string('title', 150);
            $table->foreignId('generated_by_id')->constrained('users')->cascadeOnDelete();
            $table->json('parameters')->nullable(); // stamp_paper_margin, rebate_pct, thermal_width, etc.
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'document_number']);
            $table->index(['company_id', 'installment_agreement_id']);
            $table->index(['company_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
