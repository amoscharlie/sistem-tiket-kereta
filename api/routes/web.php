<?php

use App\Http\Controllers\GoogleController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google', [GoogleController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

Route::get('/', function () {
    return response(
        json_encode(['message' => 'API is accessible'], JSON_PRETTY_PRINT)."\n",
        200,
        ['Content-Type' => 'text/plain; charset=UTF-8'],
    );
});
