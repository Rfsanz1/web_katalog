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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kledo_invoice_id')->nullable()->after('id');
            $table->string('kledo_sync_status')->nullable()->after('kledo_invoice_id')
                ->comment('null = not yet processed, pending, synced, failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['kledo_invoice_id', 'kledo_sync_status']);
        });
    }
};
