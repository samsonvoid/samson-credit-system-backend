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
        // Customers table indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name', 'idx_customers_name');
            $table->index('trust_score', 'idx_customers_trust_score');
            $table->index(['current_balance', 'credit_limit'], 'idx_customers_balance');
        });

        // Credits table indexes
        Schema::table('credits', function (Blueprint $table) {
            $table->index('customer_id', 'idx_credits_customer_id');
            $table->index('status', 'idx_credits_status');
            $table->index('due_date', 'idx_credits_due_date');
            $table->index(['status', 'due_date'], 'idx_credits_status_due');
            $table->index(['customer_id', 'status'], 'idx_credits_customer_status');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('credit_id', 'idx_payments_credit_id');
            $table->index('payment_date', 'idx_payments_date');
            $table->index(['credit_id', 'payment_date'], 'idx_payments_credit_date');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('shop_name', 'idx_users_shop_name');
            $table->index('role', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_name');
            $table->dropIndex('idx_customers_trust_score');
            $table->dropIndex('idx_customers_balance');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropIndex('idx_credits_customer_id');
            $table->dropIndex('idx_credits_status');
            $table->dropIndex('idx_credits_due_date');
            $table->dropIndex('idx_credits_status_due');
            $table->dropIndex('idx_credits_customer_status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_credit_id');
            $table->dropIndex('idx_payments_date');
            $table->dropIndex('idx_payments_credit_date');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_shop_name');
            $table->dropIndex('idx_users_role');
        });
    }
};
