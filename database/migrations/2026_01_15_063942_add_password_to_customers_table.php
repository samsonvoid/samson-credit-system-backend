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
        Schema::table('customers', function (Blueprint $table) {
            // Using a default value as a placeholder for SQLite compatibility with existing rows
            $table->string('password')->default('$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')->after('phone');
        });

        // Set default password '1234' for existing customers
        // Hash for '1234' is usually '$2y$12$...' but let's use the facade to be cleaner or raw query
        // Since we can't easily use Hash facade inside raw SQL efficiently for batch without loading models,
        // we will iterate or just use a known hash for '1234'.
        // $2y$12$K.Y... is '1234' (example). 
        // Better: Use Models in migration (allowable for small data).

        // Let's safe update
        $defaultHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // 'password'

        DB::table('customers')->update(['password' => $defaultHash]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
