<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE serialized_items MODIFY COLUMN status ENUM('in_stock', 'in_transit', 'reserved', 'allocated', 'disbursed', 'repossessed') NOT NULL DEFAULT 'in_stock'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE serialized_items MODIFY COLUMN status ENUM('in_stock', 'reserved', 'allocated', 'disbursed', 'repossessed') NOT NULL DEFAULT 'in_stock'");
        }
    }
};
