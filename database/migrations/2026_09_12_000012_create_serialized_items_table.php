<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: serialized_items (Unique Physical Hardware Units)
     */
    public function up(): void
    {
        Schema::create('serialized_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->char('ulid', 26)->unique();

            // Hardware Identifiers
            $table->string('imei_1', 50)->nullable(); // Primary 15-digit IMEI
            $table->string('imei_2', 50)->nullable(); // Secondary Dual-SIM IMEI
            $table->string('serial_number', 100)->nullable(); // Factory Appliance Serial
            $table->string('asset_tag', 50)->nullable(); // Showroom Barcode Sticker
            $table->string('color', 50)->nullable(); // Variant finish

            // State Machine: in_stock -> reserved -> allocated -> disbursed -> repossessed
            $table->enum('status', ['in_stock', 'reserved', 'allocated', 'disbursed', 'repossessed'])->default('in_stock');
            $table->decimal('purchase_cost', 12, 2)->nullable(); // Wholesale Cost Basis
            $table->dateTime('received_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'branch_id', 'status']);
            $table->index(['company_id', 'product_id', 'status']);
            $table->index(['company_id', 'imei_1']);
            $table->index(['company_id', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serialized_items');
    }
};
