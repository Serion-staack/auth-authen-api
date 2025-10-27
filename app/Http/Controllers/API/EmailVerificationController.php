<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/email/verify/{id}/{hash}",
     *     summary="Verify user's email",
     *     description="Verifies a user's email using the ID and hash from the verification link.",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         required=true,
     *         description="SHA1 hash of the user's email",
     *         @OA\Schema(type="string", example="7c4a8d09ca3762af61e59520943dc26494f8941b")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully or already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid verification link",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid verification link.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\User].")
     *         )
     *     )
     * )
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully.'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/email/resend",
     *     summary="Resend email verification link",
     *     description="Resends the email verification link to the authenticated user. Requires Bearer token authentication.",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification email resent or already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification email resent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - user not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json(['message' => 'Verification email resent.'], 200);
    }
}
