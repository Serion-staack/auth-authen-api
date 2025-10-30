<?php


use App\Http\Controllers\API\AuthorizationController;
use App\Http\Controllers\API\AuthenticateController;
use App\Http\Controllers\API\EmailVerificationController;
use Illuminate\Support\Facades\Route;


    Route::post('/register', [AuthorizationController::class, 'register'])->middleware('throttle:3,1');
    Route::post('/login', [AuthorizationController::class, 'login'])->middleware('throttle:3,1');
    Route::post('/refresh', [AuthorizationController::class, 'refresh']);
    Route::post('/reset-password', [AuthenticateController::class, 'resetPassword'])->middleware('throttle:3,1');                         //3
    Route::post('/verification-code', [AuthenticateController::class, 'verifyCode'])->middleware('throttle:3,1');                        //2
    Route::post('/verification-code-password-reset',[AuthenticateController::class, 'verificationCode'])->middleware('throttle:3,1');   //1
    Route::post('/verify-login-code',[AuthorizationController::class, 'verifyLoginCode']);
    Route::get('/get_all_users', [AuthorizationController::class, 'getAllUsers']);
    Route::get('/email/verify/{id}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
    Route::post('/reset_login_code', [AuthorizationController::class, 'resetLoginCode'])->middleware('throttle:3,1');
    Route::post('/reset_verification_code', [AuthenticateController::class, 'resetVerificationCode'])->middleware('throttle:3,1');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.resend');

    Route::middleware(['auth:sanctum','verified',\App\Http\Middleware\CheckAccessTokenExpiry::class])->group(function ()
    {
    Route::post('/change-password',[AuthenticateController::class, 'changePassword']);
    Route::post('/logout', [AuthorizationController::class, 'logout']);
    Route::get('/user', [AuthorizationController::class, 'user']);
    Route::put('/update_user/{id}', [AuthorizationController::class, 'updateUser']);
    Route::delete('/delete_user/{id}', [AuthorizationController::class, 'deleteUser']);
});

