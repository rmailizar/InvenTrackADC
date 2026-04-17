<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'account_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('account_status', ['pending', 'approved', 'rejected'])
                    ->default('approved')
                    ->after('no_hp');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'account_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('account_status');
            });
        }
    }
};

