<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_requests')) {
            Schema::create('stock_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('category')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->foreign('user_id', 'restock_requests_user_id_foreign')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('processed_by', 'restock_requests_processed_by_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('stock_request_lines')) {
            Schema::create('stock_request_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_request_id');
                $table->unsignedBigInteger('item_id');
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('price')->default(0);
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('stock_request_id', 'restock_request_lines_request_id_foreign')
                    ->references('id')->on('stock_requests')->cascadeOnDelete();
                $table->foreign('item_id', 'restock_request_lines_item_id_foreign')
                    ->references('id')->on('items')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_request_lines');
        Schema::dropIfExists('stock_requests');
    }
};
