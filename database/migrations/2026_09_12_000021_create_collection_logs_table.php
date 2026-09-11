<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_agreement_id')->constrained('installment_agreements')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collection_officer_id')->constrained('users')->cascadeOnDelete();

            $table->dateTime('visit_date');
            $table->enum('interaction_type', [
                'field_visit',
                'phone_call',
                'showroom_visit',
                'guarantor_contact',
            ])->default('field_visit');

            $table->enum('interaction_status', [
                'met_customer',
                'customer_absent',
                'promise_to_pay',
                'refused_to_pay',
                'dispute_raised',
                'payment_collected',
            ])->default('met_customer');

            $table->date('promise_to_pay_date')->nullable();
            $table->decimal('promised_amount', 12, 2)->nullable();
            $table->enum('ptp_status', [
                'pending',
                'honored',
                'broken',
                'cancelled',
            ])->nullable()->index();

            $table->string('location_notes')->nullable();
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'collection_officer_id', 'visit_date'], 'col_logs_officer_date_idx');
            $table->index(['company_id', 'installment_agreement_id'], 'col_logs_agreement_idx');
            $table->index(['company_id', 'customer_id'], 'col_logs_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_logs');
    }
};
