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
        Schema::create('organizer_balances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('available_amount', 10, 2)->default(0);
            $table->decimal('withdrawn_amount', 10, 2)->default(0);
            $table->foreignUlid('user_id')->constrained()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_balances');
    }
};
