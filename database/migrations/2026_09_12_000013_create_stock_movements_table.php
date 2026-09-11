<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: stock_movements (Immutable Stock Movement Audit Ledger)
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('serialized_item_id')->nullable()->constrained('serialized_items')->nullOnDelete();
            $table->foreignId('source_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('destination_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('movement_type', [
                'purchase_receipt',
                'branch_transfer_out',
                'branch_transfer_in',
                'sale_disbursement',
                'repossession_return',
                'manual_adjustment',
            ]);
            $table->integer('quantity')->default(1);
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'movement_type']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
