<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerificationController extends Controller
{
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Email already verified.']);
            }

            return redirect(config('app.frontend_url') . '/email-verified?status=already_verified');
        }
        $request->fulfill();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Email verified successfully.']);
        }

        return redirect(config('app.frontend_url') . '/email-verified?status=success');
    }

    public function generateVerificationUrl(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }
        $user=$request->user();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );
        return response()->json(['verificationUrl' => $verificationUrl]);
    }
}
