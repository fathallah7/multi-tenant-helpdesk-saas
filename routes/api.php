<?php

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get(
        '/health',
        fn () => ApiResponse::success(
            data: [
                'status' => 'healthy',
            ],
            message: 'SupportHub API is running.',
        ),
    );

    require app_path('Modules/Tenancy/Routes/api.php');
    require app_path('Modules/Identity/Routes/api.php');

});
