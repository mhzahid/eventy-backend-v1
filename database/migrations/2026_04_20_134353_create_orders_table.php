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
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->decimal('subtotal_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 6, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0)->index();
            $table->string('status')->default('pending')->index(); //pending → confirmed  → failed → cancelled
            $table->string('payment_status', 20)->default('unpaid')->index(); //'unpaid', 'paid', 'failed', 'refunded', 'cancelled', 'expired'
            $table->string('payment_method')->nullable();
            $table->string('currency')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};