<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_id');
            $table->string('checkout_request_id')->unique();
            $table->string('phone', 20);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, completed, failed
            $table->text('response')->nullable();
            $table->timestamps();

            $table->foreign('credit_id')->references('id')->on('credits')->onDelete('cascade');
            $table->index('checkout_request_id');
        });

        // Update payments table to include mpesa_code
        Schema::table('payments', function (Blueprint $table) {
            $table->string('mpesa_code', 50)->nullable()->after('method');
            $table->string('mpesa_receipt', 50)->nullable()->after('mpesa_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_payments');
        
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['mpesa_code', 'mpesa_receipt']);
        });
    }
};