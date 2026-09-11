<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add employee attributes and foreign key to roles table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('branch_id')->constrained('roles')->nullOnDelete();
            $table->string('employee_code', 30)->nullable()->after('role');
            $table->string('cnic', 20)->nullable()->after('employee_code');
            $table->string('designation', 100)->nullable()->after('cnic');
            $table->date('joining_date')->nullable()->after('designation');
            $table->decimal('salary', 12, 2)->nullable()->after('joining_date');

            $table->unique(['company_id', 'employee_code']);
            $table->index('cnic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropUnique(['company_id', 'employee_code']);
            $table->dropIndex(['cnic']);
            $table->dropColumn([
                'role_id',
                'employee_code',
                'cnic',
                'designation',
                'joining_date',
                'salary',
            ]);
        });
    }
};
