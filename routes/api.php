<?php


use App\Http\Controllers\API\Authen_auth_api_Controller;
use App\Http\Controllers\API\AuthenticateController;
use App\Http\Controllers\API\EmailVerificationController;
use App\Http\Controllers\API\VerificationController;
use Illuminate\Support\Facades\Route;


Route::post('/register', [Authen_auth_api_Controller::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [Authen_auth_api_Controller::class, 'login'])->middleware('throttle:5,1');
Route::post('/refresh', [Authen_auth_api_Controller::class, 'refresh']);
Route::post('/reset-password', [AuthenticateController::class, 'resetPassword']);
Route::post('/verification-code', [AuthenticateController::class, 'verifyCode']);
Route::post('/verification-code-password-reset',[AuthenticateController::class, 'verificationCode']);
Route::post('/verify-login-code',[Authen_auth_api_Controller::class, 'verifyLoginCode']);
Route::get('/get_all_users', [Authen_auth_api_Controller::class, 'getAllUsers']);
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');


Route::middleware(['auth:sanctum','verified',\App\Http\Middleware\CheckAccessTokenExpiry::class])->group(function () {
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
    Route::post('/change-password',[AuthenticateController::class, 'changePassword']);
    Route::post('/logout', [Authen_auth_api_Controller::class, 'logout']);
    Route::get('/user', [Authen_auth_api_Controller::class, 'user']);
    Route::put('/update_user/{id}', [Authen_auth_api_Controller::class, 'updateUser']);
    Route::delete('/delete_user/{id}', [Authen_auth_api_Controller::class, 'deleteUser']);
});

