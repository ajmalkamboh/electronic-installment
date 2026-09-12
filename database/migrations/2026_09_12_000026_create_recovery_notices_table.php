<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: recovery_notices (Formal Legal Notices & Repossession Warrants)
     */
    public function up(): void
    {
        Schema::create('recovery_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('recovery_case_id')->constrained('recovery_cases')->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->string('notice_number', 50);
            $table->enum('notice_type', [
                'reminder_notice',
                'formal_overdue_notice',
                'guarantor_notice',
                'final_demand_notice',
                'legal_notice',
                'repossession_warrant',
            ]);
            $table->enum('recipient_type', ['customer', 'primary_guarantor', 'secondary_guarantor', 'all'])->default('customer');
            $table->string('recipient_name', 150);
            $table->string('recipient_contact', 50)->nullable();
            $table->text('recipient_address')->nullable();
            $table->decimal('overdue_amount', 12, 2);
            $table->decimal('late_fees_amount', 10, 2)->default(0.00);
            $table->decimal('total_demand_amount', 12, 2);
            $table->date('demand_deadline');
            $table->dateTime('issued_at');
            $table->foreignId('issued_by_id')->constrained('users')->cascadeOnDelete();
            $table->enum('delivery_channel', ['hand_delivery', 'registered_post', 'sms', 'whatsapp'])->default('hand_delivery');
            $table->enum('status', ['draft', 'issued', 'served', 'acknowledged'])->default('issued');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'notice_number']);
            $table->index(['company_id', 'recovery_case_id']);
            $table->index(['company_id', 'notice_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recovery_notices');
    }
};
