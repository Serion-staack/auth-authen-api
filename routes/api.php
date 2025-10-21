<?php


use App\Http\Controllers\API\Authen_auth_api_Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [Authen_auth_api_Controller::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [Authen_auth_api_Controller::class, 'login'])->middleware('throttle:5,1');
Route::post('/refresh', [Authen_auth_api_Controller::class, 'refresh']);
Route::middleware(['auth:sanctum',\App\Http\Middleware\CheckAccessTokenExpiry::class])->group(function () {
    Route::post('/logout', [Authen_auth_api_Controller::class, 'logout']);
    Route::get('/user', [Authen_auth_api_Controller::class, 'user']);
});


