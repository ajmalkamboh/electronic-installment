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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('currency');
            $table->text('receipt_header')->nullable()->after('logo');
            $table->text('receipt_footer')->nullable()->after('receipt_header');
            $table->text('terms_conditions')->nullable()->after('receipt_footer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo', 'receipt_header', 'receipt_footer', 'terms_conditions']);
        });
    }
};
