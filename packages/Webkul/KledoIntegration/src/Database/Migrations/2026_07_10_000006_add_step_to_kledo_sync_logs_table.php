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
        Schema::table('kledo_sync_logs', function (Blueprint $table) {
            $table->string('step')->nullable()->after('order_id')
                ->comment('Which step produced this log: contact | invoice | item-{id} | payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kledo_sync_logs', function (Blueprint $table) {
            $table->dropColumn('step');
        });
    }
};
