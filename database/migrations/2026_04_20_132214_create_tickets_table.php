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
        Schema::create('tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // General, VIP, Early Bird
            $table->decimal('price', 8, 2)->default(0);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->unsignedMediumInteger('reserved_quantity')->default(0);
            $table->string('status', 25)->default('active'); // active, hidden
            $table->string('includes', 285)->nullable(); // e.g. "Access to after-party, Free drink"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};