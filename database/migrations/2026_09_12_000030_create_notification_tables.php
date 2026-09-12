<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Notification Gateway Settings per Tenant Company
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sms_driver')->default('log'); // log, generic_http, twilio
            $table->string('sms_sender_id', 50)->nullable(); // e.g. 'KAMBOH-ELEC', 'AL-MADINA'
            $table->text('sms_api_key')->nullable();
            $table->text('sms_api_secret')->nullable();
            $table->text('sms_endpoint_url')->nullable();
            $table->string('whatsapp_driver')->default('log'); // log, meta_cloud, twilio
            $table->string('whatsapp_phone_number_id', 100)->nullable();
            $table->text('whatsapp_access_token')->nullable();
            $table->string('whatsapp_business_account_id', 100)->nullable();
            $table->boolean('auto_receipt_sms')->default(true);
            $table->boolean('auto_receipt_whatsapp')->default(false);
            $table->boolean('auto_welcome_sms')->default(true);
            $table->unsignedSmallInteger('auto_reminder_days_before')->default(3);
            $table->boolean('auto_overdue_sms')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Notification Templates (Bilingual / Customizable with Variable Interpolation)
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60); // welcome_agreement, payment_receipt, due_reminder, overdue_alert, guarantor_notice, noc_clearance
            $table->string('name', 120);
            $table->string('channel', 20)->default('both'); // sms, whatsapp, both
            $table->string('subject', 191)->nullable();
            $table->text('body');
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        // 3. Notification Outbox and Audit Log
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('installment_agreement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('sms'); // sms, whatsapp
            $table->string('recipient_phone', 40)->index();
            $table->string('recipient_name', 120)->nullable();
            $table->string('template_code', 60)->nullable()->index();
            $table->text('content');
            $table->string('status', 30)->default('queued')->index(); // queued, sent, delivered, failed
            $table->string('provider', 50)->default('log'); // log, generic_http, twilio, meta_cloud
            $table->string('provider_reference', 191)->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notification_settings');
    }
};
