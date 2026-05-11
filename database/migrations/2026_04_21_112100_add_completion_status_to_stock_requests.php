<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite used by tests does not support MySQL enum modification syntax.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending'");
        }

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->foreignId('completed_by')->nullable()->after('processed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['completed_by', 'completed_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        }
    }
};
