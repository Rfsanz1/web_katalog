<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kledo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('status')->default('failed')->comment('synced | failed');
            $table->longText('response_body')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kledo_sync_logs');
    }
};
