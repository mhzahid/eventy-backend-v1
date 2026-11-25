<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->char('organizer_id', 26);
            $table->date('stat_date');
            $table->unsignedInteger('total_sold')->default(0);
            $table->timestamps();

            $table->unique(['organizer_id', 'stat_date']);
            $table->index(['organizer_id', 'stat_date']);
        });

        Schema::create('organizer_stats_monthly', function (Blueprint $table) {
            $table->id();
            $table->char('organizer_id', 26);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('total_sold')->default(0);
            $table->timestamps();

            $table->unique(['organizer_id', 'year', 'month']);
            $table->index(['organizer_id', 'year', 'month']);
        });

        Schema::create('organizer_stats_yearly', function (Blueprint $table) {
            $table->id();
            $table->char('organizer_id', 26);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_sold')->default(0);
            $table->timestamps();

            $table->unique(['organizer_id', 'year']);
            $table->index(['organizer_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_stats_daily');
        Schema::dropIfExists('organizer_stats_monthly');
        Schema::dropIfExists('organizer_stats_yearly');
    }
};
