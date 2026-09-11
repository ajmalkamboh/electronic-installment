<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->constrained('installment_schedules')->cascadeOnDelete();

            $table->decimal('amount_allocated', 12, 2);
            $table->decimal('late_fee_component', 12, 2)->default(0.00);
            $table->decimal('principal_component', 12, 2)->default(0.00);
            $table->decimal('markup_component', 12, 2)->default(0.00);

            $table->timestamps();

            $table->index(['payment_id', 'installment_schedule_id'], 'pay_alloc_payment_sched_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
