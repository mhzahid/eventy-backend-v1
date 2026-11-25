<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Covers: WHERE payment_status = 'paid' AND event_id IN (...) AND created_at >= ?
            $table->index(['event_id', 'payment_status', 'created_at'], 'orders_event_status_created_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Covers: JOIN on order_id + SELECT quantity
            $table->index(['order_id', 'quantity'], 'order_items_order_quantity_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_event_status_created_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_quantity_idx');
        });
    }
};
