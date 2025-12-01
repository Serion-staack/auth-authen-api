<?php


use App\Http\Controllers\API\AuthorizationController;
use App\Http\Controllers\API\AuthenticateController;
use App\Http\Controllers\API\EmailVerificationController;
use Illuminate\Support\Facades\Route;



         /* Route::post('/reset-password', [AuthenticateController::class, 'resetttPassword']);*/    //3
         /*Route::post('/verification-code', [AuthenticateController::class, 'verifyCode']); */   //2
         /*Route::post('/verification-code-password-reset',[AuthenticateController::class, 'verificationCode']);*/  //1

        Route::middleware('throttle:3,1')->group(function () {
           Route::post('/register', [AuthorizationController::class, 'register']);
           Route::post('/verify-login-code',[AuthorizationController::class, 'verifyLoginCode']);
           Route::post('/password/forgot', [AuthorizationController::class, 'sendResetLink']);  //new
           Route::post('/password/reset', [AuthorizationController::class, 'resetPassword'])->name('password.reset');  //new
       });

       Route::middleware('throttle:2,1')->group(function () {
          Route::post('/reset_login_code', [AuthorizationController::class, 'resetLoginCode']);
          Route::post('/reset_verification_code', [AuthenticateController::class, 'resetVerificationCode']);
          Route::post('/email/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
       });


     Route::post('/login', [AuthorizationController::class, 'login'])->middleware('throttle:5,1');
     Route::get('/email/verify/{id}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');


   Route::middleware(['auth:sanctum','verified','token_expiry'])->group(function ()
   {
    Route::post('/change-password',[AuthenticateController::class, 'changePassword']);
    Route::post('/logout', [AuthorizationController::class, 'logout']);
    Route::get('/user', [AuthorizationController::class, 'user']);
    Route::put('/update_user/{id}', [AuthorizationController::class, 'updateUser']);
    Route::delete('/delete_user/{id}', [AuthorizationController::class, 'deleteUser']);
    Route::middleware(['is_admin'])->get('/get_all_users', [AuthorizationController::class, 'getAllUsers']);
    });

    Route::middleware(['auth:sanctum','token_expiry'])->post('/refresh', [AuthorizationController::class, 'refresh']);






