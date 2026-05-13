<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->index('name', 'idx_items_name');
            $table->index('category', 'idx_items_category');
            $table->index('is_active', 'idx_items_active');
            $table->index(['is_active', 'category'], 'idx_items_active_category');
        });

        Schema::table('credit_items', function (Blueprint $table) {
            $table->index('credit_id', 'idx_credit_items_credit_id');
            $table->index('item_id', 'idx_credit_items_item_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('customer_id', 'idx_transactions_customer_id');
            $table->index('type', 'idx_transactions_type');
            $table->index('created_at', 'idx_transactions_created');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_name');
            $table->dropIndex('idx_items_category');
            $table->dropIndex('idx_items_active');
            $table->dropIndex('idx_items_active_category');
        });

        Schema::table('credit_items', function (Blueprint $table) {
            $table->dropIndex('idx_credit_items_credit_id');
            $table->dropIndex('idx_credit_items_item_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_transactions_customer_id');
            $table->dropIndex('idx_transactions_type');
            $table->dropIndex('idx_transactions_created');
        });
    }
};