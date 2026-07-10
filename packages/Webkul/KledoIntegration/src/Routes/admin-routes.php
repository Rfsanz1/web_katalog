<?php

use Illuminate\Support\Facades\Route;
use Webkul\KledoIntegration\Http\Controllers\Admin\KledoSyncController;

Route::controller(KledoSyncController::class)
    ->prefix('kledo')
    ->group(function () {
        Route::get('/', 'index')->name('admin.kledo.sync.index');

        Route::post('retry/{orderId}', 'retry')->name('admin.kledo.sync.retry');

        Route::get('test-connection', 'testConnection')->name('admin.kledo.sync.test-connection');
    });
