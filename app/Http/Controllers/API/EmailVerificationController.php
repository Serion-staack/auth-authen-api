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
     *     path="/api/email/verify/{id}",
     *     summary="Verify user's email",
     *     description="Verifies a user's email using the ID from the signed verification link.",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully or already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Invalid or expired signed URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid signature.")
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

    public function verify(Request $request, $id)
    {

       if(!$request->hasValidSignature())
       {
           return response()->json(['message' => 'Invalid or expired verification link',]);
       }

        $user = User::findOrFail($id);

        if ($user->hasVerifiedEmail())
        {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return response()->json(['message' => 'Email verified successfully.'], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/email/resend",
     *     summary="Resend email verification",
     *     description="Resends the email verification link to the user if the email is not verified yet.",
     *     operationId="resendVerificationEmail",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="The email of the user to resend verification link")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification email resent.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email',$request->email)->first();

        if(!$user)
        {
            return response()->json(['message' => 'User not found.'], 404);
        }

        /*$user = $request->user();*/

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $user->notify(new VerifyEmailNotification());

        return response()->json(['message' => 'Verification email resent.'], 200);
    }
}
