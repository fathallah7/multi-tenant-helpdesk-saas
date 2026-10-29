<?php

use App\Modules\Tenancy\Http\Controllers\TenantController;
use App\Modules\Tenancy\Http\Controllers\TenantMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tenants', [
        TenantController::class,
        'index',
    ]);

    Route::post('/tenants', [
        TenantController::class,
        'store',
    ]);

    Route::get('/tenants/{tenant}', [
        TenantController::class,
        'show',
    ]);

    Route::get('/tenants/{tenant}/members', [
        TenantMemberController::class,
        'index',
    ]);
});
