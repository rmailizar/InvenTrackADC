<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_requests') && !Schema::hasTable('stuff_requests')) {
            Schema::rename('stock_requests', 'stuff_requests');
        }

        if (Schema::hasTable('stock_request_items') && !Schema::hasTable('stuff_request_items')) {
            Schema::rename('stock_request_items', 'stuff_request_items');
        }

        if (Schema::hasTable('stuff_request_items') && Schema::hasColumn('stuff_request_items', 'stock_request_id')) {
            Schema::table('stuff_request_items', function (Blueprint $table) {
                $table->renameColumn('stock_request_id', 'stuff_request_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stuff_request_items') && !Schema::hasTable('stock_request_items')) {
            if (Schema::hasColumn('stuff_request_items', 'stuff_request_id')) {
                Schema::table('stuff_request_items', function (Blueprint $table) {
                    $table->renameColumn('stuff_request_id', 'stock_request_id');
                });
            }

            Schema::rename('stuff_request_items', 'stock_request_items');
        }

        if (Schema::hasTable('stuff_requests') && !Schema::hasTable('stock_requests')) {
            Schema::rename('stuff_requests', 'stock_requests');
        }
    }
};
