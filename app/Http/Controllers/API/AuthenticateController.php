<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\User;
use App\Notifications\OTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthenticateController extends Controller
{

    /**
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT"
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/verification-code-password-reset",
     *     summary="Send Verification Code to user email for password reset",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification Code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification Code sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Email not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Email not found")
     *         )
     *     )
     * )
     */

    public function verificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $user = User::where('email',$request->email)->first();

        if(!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

        if ($user->password_reset_code_expires_at && now()->lessThan($user->password_reset_code_expires_at)) {
            return response()->json(['error' => 'A reset code is already active. Please wait until it expires.'], 429);
        }

        $passwordResetCode = rand(100000, 999999);

        $user->password_reset_code = Hash::make($passwordResetCode);

        $user->password_reset_code_expired_at = now()->addMinutes(10);

        $user->save();

        $user->notify(new OTPMail($passwordResetCode));

        return response()->json(['message' => 'Password Reset Code sent to your email'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/password-reset-code",
     *     summary="Verify Password Reset Code for password reset",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password_reset_code"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="password_reset_code", type="integer", example=123456)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification Code verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification Code verified successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid Verification Code or email not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid Verification Code")
     *         )
     *     )
     * )
     */

    public function verifyCode(Request $request)
    {
        $request->validate([
            'password_reset_code' => 'required|digits:6',
            'email' => 'required|email',
        ]);

        $user = User::where('email',$request->email)->first();

        if (!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

        if(!$user->password_reset_code || !$user->password_reset_code_expired_at)
        {
            return response()->json(['error' => 'No password reset code request found'], 401);
        }

        if(now()->greaterThan($user->password_reset_code_expired_at))
        {
            $user->password_reset_code = null;

            $user->password_reset_code_expired_at=null;

            $user->save();

            return response()->json(['error' => 'Your password reset code has been expired.'], 200);
        }

        if(!Hash::check($request->password_reset_code,$user->password_reset_code))
        {
            return response()->json(['error' => 'Invalid password reset code'], 401);
        }

        $user->password_reset_code = null;

        $user->password_reset_code_expired_at=null;

        $user->save();

        return response()->json(['message' => 'Password reset code has been verified successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/change-password",
     *     summary="Change authenticated user's password",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","new_password","new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword123"),
     *             @OA\Property(property="new_password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="New password cannot be the same as current password",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="New password cannot be the same as current password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid current password",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid current password")
     *         )
     *     )
     * )
     */

      public function changePassword(ChangePasswordRequest $request)
      {
          $validated=$request->validated();

          $user=$request->user();

          if(!$user)
          {
              return response()->json(['error' => 'Unauthenticated'], 401);
          }

          if(!Hash::check($validated['current_password'],$user->password))
          {
              return response()->json(['error' => 'Your current password is incorrect'], 401);
          }

          if(Hash::check($validated['new_password'],$user->password))
          {
              return response()->json(['error' => 'New Password cannot be same as your current password'], 401);
          }
          $user->update([
              'password'=>Hash::make($validated['new_password'])
          ]);

         $currentTokenId = $user->currentAccessToken()?->id;

        $user->tokens()->when($currentTokenId, fn($q) => $q->where('id', '!=', $currentTokenId))
        ->delete();

          return response()->json(['message' => 'Password Changed succesfully'], 200);
      }



    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset user password using Verification_Code",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","verification_code","password"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="verification_code", type="integer", example=123456),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid email or Verification Code",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid Verification Code")
     *         )
     *     )
     * )
     */

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:12|regex:/[0-9]/|regex:/[A-Z]/|regex:/[a-z]/|regex:/[@$!%*#?&]/',
            'verification_code' => 'required|digits:6',
        ]);

        $user = User::where('email',$request->email)->first();

        if(!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

       /* if($user->email != $request->email)
        {
            return response()->json(['error' => 'Invalid Verification Code'], 401);
        }*/

        if(!$user->verification_code || !$user->verification_code_expired_at || now()->greaterThan($user->verification_code_expired_at) || !Hash::check($request->verification_code, $user->verification_code)) {
            return response()->json(['error' => 'Invalid or expired verification code'], 401);
        }

        if (Hash::check($request->password, $user->password))
        {
            return response()->json(['message' => 'The new password cannot be the same as the old password'], 400);
        }

        $user->password=Hash::make($request->password);
        $user->verification_code=null;
        $user->verification_code_expired_at	=null;
        $user->save();

        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully'], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/reset_verification_code",
     *     summary="Resend a new verification code to the user's email",
     *     description="Sends a new 6-digit verification code if the previous one has expired.",
     *     operationId="resetVerificationCode",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A new verification code has been sent to your email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A new verification code has been sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Email not found or previous code still valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Previous code is still valid, please wait until it expires")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"email": {"The email field is required."}}
     *             )
     *         )
     *     )
     * )
     */

    public function resetVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user=User::where('email',$request->email)->first();

        if(!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

        if($user->password_reset_code_expired_at && now()->lessThan($user->password_reset_code_expired_at))
        {
            return response()->json(['error' => 'Previous code is still valid,please wait until it expires'], 401);
        }

        $new_code=rand(100000, 999999);

        $user->password_reset_code = Hash::make($new_code);

        $user->password_reset_code_expired_at=now()->addMinutes(4);

        $user->save();

        $user->notify(new OTPMail($new_code));

        return response()->json(['message' => 'A new verification code has been sent to your email'], 200);
    }
}
