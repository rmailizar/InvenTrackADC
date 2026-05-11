<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('items', 'volume')) {
            DB::table('items')->whereNotNull('volume')->update(['volume' => null]);
        }

        if (Schema::hasColumn('transactions', 'volume') && Schema::hasColumn('transactions', 'quantity')) {
            DB::table('transactions')
                ->where('bidang', 'teknik')
                ->update(['volume' => DB::raw('quantity')]);
        }
    }

    public function down(): void
    {
        //
    }
};
