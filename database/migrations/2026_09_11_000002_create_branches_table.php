<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: branches
     * Purpose: Operational branch, showroom, or outlet belonging to a company.
     * Ownership: Belongs strictly to a company.
     * Relationships: belongsTo(Company::class), hasMany(User::class).
     * Indexes: company_id (index/FK), ulid (unique), status (index), [company_id, code] (composite unique).
     * Foreign Keys: company_id -> companies(id) ON DELETE CASCADE.
     * Unique Constraints: ulid, [company_id, code].
     * Soft Deletion: Soft deletes enabled to protect transaction and branch history.
     * Audit Requirements: is_main flag, status, timestamps, soft deletes.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('code', 30);
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_main')->default(false);
            $table->string('status', 20)->default('active')->index(); // active, inactive
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
