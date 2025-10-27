<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerificationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/email/verification-notification",
     *     summary="Resend email verification link",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification email sent successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }

    /**
     * @OA\Get(
     *     path="/api/email/verify/{id}/{hash}",
     *     summary="Verify a user's email address",
     *     description="Handles email verification requests. If the email is already verified, it returns a relevant message or redirects to the frontend. If not verified, it fulfills verification and redirects or responds with JSON.",
     *     operationId="verifyEmail",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user whose email is being verified",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         required=true,
     *         description="The email verification hash",
     *         @OA\Schema(type="string", example="abcdef1234567890")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified or already verified (JSON response)",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Email verified successfully.")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Email already verified.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirects to frontend confirmation page with status parameter",
     *         @OA\Header(
     *             header="Location",
     *             description="Frontend redirect URL (e.g., /email-verified?status=success or /email-verified?status=already_verified)",
     *             @OA\Schema(type="string", example="https://your-frontend.com/email-verified?status=success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired verification link"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/email/generate-verification-url",
     *     summary="Generate a temporary email verification link for the authenticated user",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success response",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="verification_url", type="string", example="http://localhost:8000/api/verify-email/1/9b7c7f03b3f75f7d1bb5cbfd7ad9f5b7f056882c?expires=1735300078&signature=...")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Email already verified.")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

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
