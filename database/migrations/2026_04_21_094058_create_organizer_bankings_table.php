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
        Schema::create('organizer_bankings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->text('account_number');
            $table->string('routing_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->foreignUlid('user_id')->constrained()->unique(); // one-to-one
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_bankings');
    }
};