<?php

use App\Modules\Identity\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [
        RegisterController::class,
        'store',
    ])->middleware('throttle:5,1');
});
