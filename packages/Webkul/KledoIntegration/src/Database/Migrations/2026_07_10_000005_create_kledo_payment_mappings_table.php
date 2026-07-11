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
        Schema::create('kledo_payment_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method_code')->unique()
                ->comment('Bagisto payment method code, e.g. cashondelivery, stripe');
            $table->unsignedBigInteger('finance_account_id')
                ->comment('Kledo COA (chart-of-accounts) ID for this payment method');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kledo_payment_mappings');
    }
};
