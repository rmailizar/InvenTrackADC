<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stuff_requests')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stuff_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled', 'cancel') DEFAULT 'pending'");
        }

        DB::table('stuff_requests')
            ->where('status', 'cancelled')
            ->update(['status' => 'cancel']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stuff_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'cancel') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('stuff_requests')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stuff_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'cancel', 'cancelled') DEFAULT 'pending'");
        }

        DB::table('stuff_requests')
            ->where('status', 'cancel')
            ->update(['status' => 'cancelled']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stuff_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending'");
        }
    }
};
