<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: branch_inventories (Aggregated Showroom Stock Levels)
     */
    public function up(): void
    {
        Schema::create('branch_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->integer('quantity_on_hand')->default(0); // Total physical units in showroom
            $table->integer('quantity_reserved')->default(0); // Reserved for draft/approved contracts pending down payment
            $table->integer('quantity_available')->default(0); // Net sellable: on_hand - reserved

            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_inventories');
    }
};
