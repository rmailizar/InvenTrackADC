<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_request_id')->constrained('stock_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        foreach (DB::table('stock_requests')->orderBy('id')->get() as $row) {
            DB::table('stock_request_items')->insert([
                'stock_request_id' => $row->id,
                'item_id' => $row->item_id,
                'quantity' => $row->quantity,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn(['item_id', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->after('bidang')->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1)->after('item_id');
        });

        $grouped = DB::table('stock_request_items')->orderBy('id')->get()->groupBy('stock_request_id');
        foreach ($grouped as $stockRequestId => $rows) {
            $first = $rows->first();
            DB::table('stock_requests')->where('id', $stockRequestId)->update([
                'item_id' => $first->item_id,
                'quantity' => $first->quantity,
            ]);
        }

        Schema::dropIfExists('stock_request_items');
    }
};
