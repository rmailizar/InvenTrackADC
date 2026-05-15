<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Normalisasi data lama sebelum enum diubah
        DB::table('users')
            ->where('role', 'manager')
            ->update(['role' => 'manajer']);

        DB::table('users')
            ->where('role', 'staff')
            ->update(['role' => 'staf']);

        DB::table('users')
            ->whereNull('role')
            ->update(['role' => 'staf']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'admin', 'manajer', 'staf'])
                ->default('staf')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'superadmin')
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where('role', 'manajer')
            ->update(['role' => 'manager']);

        DB::table('users')
            ->where('role', 'staf')
            ->update(['role' => 'staff']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'manager', 'staff'])
                ->default('staff')
                ->change();
        });
    }
};