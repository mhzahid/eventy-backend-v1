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
        Schema::create('events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique(); // for subdomain
            $table->string('tag_line')->nullable();
            $table->string('prefix', 5)->unique();
            $table->text('description')->nullable();
            $table->string('location', 350)->nullable();
            $table->string('map_url', 350)->nullable();
            $table->boolean('is_physical')->default(true);
            $table->string('status')->default('draft');
            $table->timestamp('event_date');
            $table->timeTz('start_time');
            $table->timeTz('sell_end_time');
            $table->boolean('is_age_restricted')->default(false);
            $table->boolean('charge_transaction_fee')->default(false);
            $table->unsignedInteger('max_tickets_per_order')->default(5);
            $table->unsignedInteger('max_tickets_per_user')->default(5);
            $table->string('brand_color')->nullable();
            $table->string('event_banner', 275)->nullable();
            $table->json('sponsors')->nullable();
            $table->json('highlights')->nullable();
            $table->string('support_email', 255)->nullable();
            $table->string('support_number', 18)->nullable();
            $table->string('facebook_url', 350)->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('meta_data')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_on_hold')->default(false);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_upcoming')->default(false);
            $table->foreignUlid('user_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('payment_gateway_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};