<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah sementara menjadi VARCHAR agar bisa menerima teks apa saja
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type VARCHAR(255)");
    
        // 2. Sekarang update datanya tanpa terhalang aturan ENUM
        DB::table('transactions')->where('type', 'masuk')->update(['type' => 'in']);
        DB::table('transactions')->where('type', 'keluar')->update(['type' => 'out']);
    
        // 3. Kembalikan menjadi ENUM dengan opsi yang baru (English)
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('in', 'out') NOT NULL");
    }
    
    public function down(): void
    {
        // Lakukan hal yang sama jika ingin rollback
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type VARCHAR(255)");
        
        DB::table('transactions')->where('type', 'in')->update(['type' => 'masuk']);
        DB::table('transactions')->where('type', 'out')->update(['type' => 'keluar']);
    
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('masuk', 'keluar') NOT NULL");
    }
};