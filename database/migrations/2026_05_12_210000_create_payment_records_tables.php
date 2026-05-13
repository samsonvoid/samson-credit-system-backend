<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment Initiation - tracks when debtor starts payment
        Schema::create('payment_initiations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_id');
            $table->string('customer_phone', 20);
            $table->string('payment_ref', 100)->unique(); // Unique payment reference
            $table->decimal('amount', 12, 2);
            $table->timestamp('initiated_at');
            $table->timestamp('confirmed_at')->nullable(); // When debtor clicked "Nimefanya"
            $table->string('status', 30)->default('pending_verification');
            // Status: pending_verification, awaiting_admin_confirmation, verified, expired
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('credit_id')->references('id')->on('credits')->onDelete('cascade');
            $table->index('payment_ref');
            $table->index('status');
            $table->index(['credit_id', 'status']);
        });

        // Pending Payments - Admin verification queue
        Schema::create('pending_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_id');
            $table->string('payment_ref', 100); // Links to payment_initiation
            $table->decimal('amount', 12, 2);
            $table->timestamp('initiated_at');
            $table->timestamp('confirmed_at')->nullable(); // When debtor confirmed
            $table->string('customer_phone', 20);
            $table->string('status', 20)->default('pending');
            // Status: pending, confirmed, rejected
            $table->unsignedBigInteger('admin_id')->nullable(); // Who confirmed/rejected
            $table->string('rejected_reason', 255)->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->foreign('credit_id')->references('id')->on('credits')->onDelete('cascade');
            $table->index('payment_ref');
            $table->index('status');
            $table->index(['credit_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_payments');
        Schema::dropIfExists('payment_initiations');
    }
};