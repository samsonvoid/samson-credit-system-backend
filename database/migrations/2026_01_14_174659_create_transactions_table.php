<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            // Optional: link to backend user if we had auth. For now, maybe just nullable? 
            // The prompt says "user_id" (Shopkeeper). 
            // Since we haven't implemented Auth fully, let's make it nullable or just assume ID 1 for now.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // We should definitely link to the Customer involved
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->string('type'); // CREDIT_ISSUE, PAYMENT_RECEIVED, etc.
            $table->string('circulation_type')->default('CASH'); // CASH, PRODUCT
            $table->enum('direction', ['in', 'out']); // in = payment from customer, out = credit to customer
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('reference_id')->nullable(); 
            $table->json('metadata')->nullable(); // For receipts or extra data
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
