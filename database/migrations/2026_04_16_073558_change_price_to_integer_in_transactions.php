<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ✅ Step 1: ubah NULL jadi 0 dulu
        DB::table('transactions')
            ->whereNull('price')
            ->update(['price' => 0]);

        // ✅ Step 2: ubah tipe kolom ke integer
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('price')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->change();
        });
    }
};