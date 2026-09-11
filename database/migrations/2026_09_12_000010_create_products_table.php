<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: products (Catalog Master)
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->char('ulid', 26)->unique();

            $table->string('brand', 100); // e.g. Samsung, Haier, Apple, Dawlance, Pel
            $table->string('model_name', 191); // e.g. Galaxy S25 Ultra, 1.5 Ton DC Inverter AC
            $table->string('sku', 50)->nullable(); // Internal Stock Keeping Unit
            $table->decimal('base_cash_price', 12, 2); // Showroom Cash Retail Price (PKR)
            $table->decimal('min_down_payment_pct', 5, 2)->default(20.00); // e.g. 20.00%
            $table->boolean('is_serialized')->default(true); // Mandatory IMEI/Serial tracking
            $table->text('description')->nullable();
            $table->json('specifications')->nullable(); // Technical specs (RAM, Storage, Capacity, Color)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'brand']);
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
