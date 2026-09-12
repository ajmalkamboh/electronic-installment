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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('source_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->char('ulid', 26)->unique();

            $table->string('transfer_number', 50); // e.g. TRF-202609-0001
            $table->enum('status', [
                'draft',
                'requested',
                'approved',
                'dispatched',
                'received',
                'rejected',
                'cancelled',
            ])->default('draft')->index();

            // Staff Stakeholders
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();

            // Logistics & Driver Information
            $table->string('driver_name', 191)->nullable();
            $table->string('driver_cnic', 25)->nullable();
            $table->string('driver_phone', 25)->nullable();
            $table->string('vehicle_number', 50)->nullable(); // e.g. LEA-24-9182
            $table->string('transport_company', 191)->nullable(); // e.g. Showroom Van, TCS, Al-Madina Cargo

            // Security Gate Pass Details
            $table->string('gate_pass_number', 50)->nullable(); // e.g. GP-202609-0001
            $table->timestamp('gate_pass_generated_at')->nullable();
            $table->text('gate_pass_notes')->nullable();

            // Metrics & Timestamps
            $table->unsignedInteger('total_items_count')->default(0);
            $table->unsignedInteger('total_received_count')->default(0);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();

            // Notes & Reasons
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'transfer_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'source_branch_id']);
            $table->index(['company_id', 'destination_branch_id']);
            $table->index(['company_id', 'gate_pass_number']);
        });

        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transfer_id')->constrained('inventory_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('serialized_item_id')->nullable()->constrained('serialized_items')->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->enum('status', [
                'pending',
                'dispatched',
                'received',
                'rejected',
                'damaged',
            ])->default('pending');

            $table->enum('received_condition', [
                'good',
                'damaged',
                'missing',
            ])->nullable();

            $table->text('item_notes')->nullable();
            $table->timestamps();

            $table->index(['inventory_transfer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
    }
};
