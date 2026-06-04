<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'component')) {
                $table->string('component')->nullable()->after('category');
            }
        });

        if (Schema::hasColumn('items', 'component')) {
            DB::table('items')
                ->where('bidang', 'teknik')
                ->whereNull('component')
                ->update(['component' => DB::raw('category')]);
        }

    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'component')) {
                $table->dropColumn('component');
            }
        });
    }
};
