<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('deductions')) {
            return;
        }

        // Step 1: Add 'approved' to the enum while keeping 'applied'
        DB::statement("ALTER TABLE deductions MODIFY status ENUM('pending', 'applied', 'rejected', 'approved') DEFAULT 'pending'");

        // Step 2: Migrate any existing 'applied' values to 'approved'
        DB::table('deductions')->where('status', 'applied')->update(['status' => 'approved']);

        // Step 3: Remove 'applied' from the enum
        DB::statement("ALTER TABLE deductions MODIFY status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('deductions')) {
            return;
        }

        // Reverse: Add 'applied' back and convert 'approved' to 'applied'
        DB::statement("ALTER TABLE deductions MODIFY status ENUM('pending', 'approved', 'rejected', 'applied') DEFAULT 'pending'");
        DB::table('deductions')->where('status', 'approved')->update(['status' => 'applied']);
        DB::statement("ALTER TABLE deductions MODIFY status ENUM('pending', 'applied', 'rejected') DEFAULT 'pending'");
    }
};
