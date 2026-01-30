<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/redirect', [AuthController::class, 'redirect'])
    ->name('auth.redirect');

Route::get('/auth/callback', [AuthController::class, 'callback'])
    ->name('auth.callback');

// Route::get('/test-env', function () {
//     return response()->json([
//         'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
//         'api_key' => env('CLOUDINARY_API_KEY'),
//         'api_secret' => env('CLOUDINARY_API_SECRET'),
//         'config_cloud_name' => config('cloudinary.cloud_name'),
//         'config_api_key' => config('cloudinary.api_key'),
//     ]);
// });
