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
        Schema::create('organizer_owner_infos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('id_type')->default('national_id'); // passport, national_id
            $table->string('id_image', 350)->nullable();
            $table->string('phone_number')->nullable();
            $table->string('mail')->nullable();
            $table->foreignUlid('user_id')->constrained()->unique(); // one-to-one
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_owner_infos');
    }
};
