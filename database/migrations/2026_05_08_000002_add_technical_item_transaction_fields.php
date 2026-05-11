<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'no_normalisasi')) {
                $table->string('no_normalisasi')->nullable()->after('name');
            }

            if (!Schema::hasColumn('items', 'lokasi')) {
                $table->string('lokasi')->nullable()->after('bidang');
            }

            if (!Schema::hasColumn('items', 'volume')) {
                $table->unsignedInteger('volume')->nullable()->after('lokasi');
            }

            if (!Schema::hasColumn('items', 'ship_unloader')) {
                $table->string('ship_unloader')->nullable()->after('volume');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'no_normalisasi')) {
                $table->string('no_normalisasi')->nullable()->after('bidang');
            }

            if (!Schema::hasColumn('transactions', 'lokasi')) {
                $table->string('lokasi')->nullable()->after('no_normalisasi');
            }

            if (!Schema::hasColumn('transactions', 'volume')) {
                $table->unsignedInteger('volume')->nullable()->after('lokasi');
            }

            if (!Schema::hasColumn('transactions', 'ship_unloader')) {
                $table->string('ship_unloader')->nullable()->after('volume');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            foreach (['ship_unloader', 'volume', 'lokasi', 'no_normalisasi'] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('items', function (Blueprint $table) {
            foreach (['ship_unloader', 'volume', 'lokasi', 'no_normalisasi'] as $column) {
                if (Schema::hasColumn('items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
