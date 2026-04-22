<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->string('nip')->nullable()->after('requester_name');
            $table->string('jabatan')->nullable()->after('nip');
            $table->string('bidang')->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn(['nip', 'jabatan', 'bidang']);
        });
    }
};
