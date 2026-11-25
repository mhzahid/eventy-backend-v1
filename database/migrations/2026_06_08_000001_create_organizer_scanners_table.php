<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_scanners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organizer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUlid('scanner_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['organizer_id', 'scanner_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_scanners');
    }
};