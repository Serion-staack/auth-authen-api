<?php


use App\Http\Controllers\API\Authen_auth_api_Controller;
use App\Http\Controllers\API\AuthenticateController;
use App\Http\Controllers\Auth\EmailVerificationController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [Authen_auth_api_Controller::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [Authen_auth_api_Controller::class, 'login'])->middleware('throttle:5,1');
Route::post('/refresh', [Authen_auth_api_Controller::class, 'refresh']);
Route::post('/reset-password', [AuthenticateController::class, 'resetPassword']);
Route::post('/verification-code', [AuthenticateController::class, 'verifyCode']);
Route::post('/forgot-password',[AuthenticateController::class, 'forgetPassword']);

Route::middleware(['auth:sanctum',\App\Http\Middleware\CheckAccessTokenExpiry::class])->group(function () {
    Route::post('/change-password',[AuthenticateController::class, 'changePassword']);
    Route::post('/logout', [Authen_auth_api_Controller::class, 'logout']);
    Route::get('/user', [Authen_auth_api_Controller::class, 'user']);
});


