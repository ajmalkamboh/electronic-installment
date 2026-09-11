<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: users (additions)
     * Purpose: Support multi-tenancy, branch assignment, roles, and status checks.
     * Ownership: Company (nullable for platform-level super admins), Branch (nullable).
     * Relationships: belongsTo(Company::class), belongsTo(Branch::class).
     * Indexes: company_id, branch_id, ulid, status, role.
     * Foreign Keys:
     *   - company_id -> companies(id) ON DELETE CASCADE
     *   - branch_id -> branches(id) ON DELETE SET NULL
     * Unique Constraints: ulid.
     * Soft Deletion: Soft deletes enabled.
     * Audit Requirements: status, role, last_login_at, last_login_ip, timestamps, soft deletes.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('ulid')->after('id')->unique();
            $table->foreignId('company_id')->nullable()->after('ulid')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
            $table->string('role', 30)->default('company_admin')->after('password')->index();
            $table->string('status', 20)->default('active')->after('role')->index(); // active, suspended, inactive
            $table->string('phone', 30)->nullable()->after('status');
            $table->string('avatar')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'ulid',
                'company_id',
                'branch_id',
                'role',
                'status',
                'phone',
                'avatar',
                'last_login_at',
                'last_login_ip',
                'deleted_at'
            ]);
        });
    }
};
