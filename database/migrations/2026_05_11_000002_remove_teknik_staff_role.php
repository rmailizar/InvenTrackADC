<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('bidang', 'teknik')
            ->where('role', 'staf')
            ->update([
                'role' => 'admin',
                'account_status' => 'approved',
            ]);

        DB::table('users')
            ->where('bidang', 'teknik')
            ->whereIn('role', ['admin', 'manajer'])
            ->where('account_status', 'pending')
            ->update(['account_status' => 'approved']);
    }

    public function down(): void
    {
        //
    }
};
