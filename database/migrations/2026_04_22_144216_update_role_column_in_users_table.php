<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mengubah struktur enum dan default value
            $table->enum('role', ['admin', 'manajer', 'staf'])
                ->default('staf')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mengembalikan ke struktur awal jika migration di-rollback
            $table->enum('role', ['admin', 'manager', 'staff'])
                ->default('staff')
                ->change();
        });
    }
};