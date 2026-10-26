<?php

use App\Modules\Tenancy\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tenants', [
        TenantController::class,
        'index',
    ])->middleware('auth:sanctum');

    Route::post('/tenants', [
        TenantController::class,
        'store',
    ]);
});
