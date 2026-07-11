<?php

use Illuminate\Support\Facades\Route;
use Webkul\KledoIntegration\Http\Controllers\Admin\KledoPaymentMappingController;
use Webkul\KledoIntegration\Http\Controllers\Admin\KledoSyncController;

/*
|--------------------------------------------------------------------------
| Kledo Integration — Admin Routes
|--------------------------------------------------------------------------
| All routes inherit the web + admin + NoCacheMiddleware middleware stack
| and the admin URL prefix from KledoIntegrationServiceProvider.
*/

Route::prefix('kledo')->group(function () {

    // -----------------------------------------------------------------------
    // Sync status / order list
    // -----------------------------------------------------------------------
    Route::controller(KledoSyncController::class)->group(function () {
        // Index: list orders with kledo_sync_status (filterable)
        Route::get('/', 'index')->name('admin.kledo.sync.index');

        // Detail: sync log entries for a specific order
        Route::get('orders/{orderId}/logs', 'show')->name('admin.kledo.sync.show');

        // Retry: re-queue the sync job for a failed order
        Route::post('orders/{orderId}/retry', 'retry')->name('admin.kledo.sync.retry');

        // Test connection (AJAX)
        Route::get('test-connection', 'testConnection')->name('admin.kledo.sync.test-connection');
    });

    // -----------------------------------------------------------------------
    // Payment method → Kledo finance account mappings
    // -----------------------------------------------------------------------
    Route::controller(KledoPaymentMappingController::class)
        ->prefix('payment-mappings')
        ->group(function () {
            Route::get('/', 'index')->name('admin.kledo.payment-mappings.index');
            Route::post('/', 'store')->name('admin.kledo.payment-mappings.store');
            Route::delete('{id}', 'destroy')->name('admin.kledo.payment-mappings.destroy');
        });
});
