<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: companies
     * Purpose: Root tenant entity for businesses operating installment agreements.
     * Ownership: Top-level tenant entity.
     * Relationships: Has many branches, users, customers, agreements, products.
     * Indexes: ulid (unique), slug (unique), status (index).
     * Foreign Keys: None.
     * Unique Constraints: ulid, slug.
     * Soft Deletion: Soft deletes enabled to protect financial records and audit trail.
     * Audit Requirements: status, timestamps, soft deletes.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('ntn_strn', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->string('status', 20)->default('active')->index(); // active, suspended, trial, cancelled
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
