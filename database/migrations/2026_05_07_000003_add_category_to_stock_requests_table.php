<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_requests') && !Schema::hasColumn('stock_requests', 'category')) {
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->string('category')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_requests') && Schema::hasColumn('stock_requests', 'category')) {
            Schema::table('stock_requests', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
