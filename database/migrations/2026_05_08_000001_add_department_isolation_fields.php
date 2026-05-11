<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'admin', 'manajer', 'staf'])
                ->default('staf')
                ->change();

            if (!Schema::hasColumn('users', 'bidang')) {
                $table->enum('bidang', ['teknik', 'umum'])->nullable()->after('role');
            }
        });

        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'bidang')) {
                $table->enum('bidang', ['teknik', 'umum'])->default('umum')->after('unit');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'bidang')) {
                $table->enum('bidang', ['teknik', 'umum'])->default('umum')->after('user_id');
            }
        });

        if (Schema::hasTable('stuff_requests')) {
            Schema::table('stuff_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('stuff_requests', 'bidang')) {
                    $table->enum('bidang', ['teknik', 'umum'])->default('umum')->after('jabatan');
                }
            });
        }

        if (Schema::hasTable('stock_requests')) {
            Schema::table('stock_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_requests', 'bidang')) {
                    $table->enum('bidang', ['teknik', 'umum'])->default('umum')->after('user_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_requests') && Schema::hasColumn('stock_requests', 'bidang')) {
            Schema::table('stock_requests', fn(Blueprint $table) => $table->dropColumn('bidang'));
        }

        if (Schema::hasTable('stuff_requests') && Schema::hasColumn('stuff_requests', 'bidang')) {
            Schema::table('stuff_requests', fn(Blueprint $table) => $table->dropColumn('bidang'));
        }

        if (Schema::hasColumn('transactions', 'bidang')) {
            Schema::table('transactions', fn(Blueprint $table) => $table->dropColumn('bidang'));
        }

        if (Schema::hasColumn('items', 'bidang')) {
            Schema::table('items', fn(Blueprint $table) => $table->dropColumn('bidang'));
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'bidang')) {
                $table->dropColumn('bidang');
            }

            $table->enum('role', ['admin', 'manajer', 'staf'])
                ->default('staf')
                ->change();
        });
    }
};
