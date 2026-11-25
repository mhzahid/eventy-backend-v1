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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('amount', 10, 2)->index();
            $table->string('status')->default('pending')->index(); // pending, processing, settled, rejected
            $table->string('note', 350)->nullable(); // organizer's note on request
            $table->string('admin_note', 350)->nullable(); // admin reason for rejection
            $table->timestamp('settled_at')->nullable(); // when the withdrawal is settled
            $table->foreignUlid('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};