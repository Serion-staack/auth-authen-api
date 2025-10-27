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
        $verification_code = rand(100000, 999999);
        $user->verification_code = $verification_code;
        $user->verification_code_expired_at = now()->addMinutes(2);
        $user->save();
        $user->notify(new OTPMail($verification_code));
        return response()->json(['message' => 'Verification Code sent to your email'], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/verification-code",
     *     summary="Verify Verification Code for password reset",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","verification_code"},
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="verification_code", type="integer", example=123456)
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
            'verification_code' => 'required|digits:6',
            'email' => 'required|email',
        ]);

        $user = User::where('email',$request->email)->first();
        if (!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

        if(!$user->verification_code || !$user->verification_code_expired_at)
        {
            return response()->json(['error' => 'No verification_code request found'], 401);
        }

        if(now()->greaterThan($user->verification_code_expired_at))
        {
            $user->verification_code=null;
            $user->verification_code_expired_at=null;
            $user->save();
            return response()->json(['error' => 'Your verification code has been expired.'], 200);
        }
        if($user->verification_code != $request->verification_code)
        {
            return response()->json(['error' => 'Invalid Verification Code'], 401);
        }

        return response()->json(['message' => 'Verification Code has been verified successfully'], 200);
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
            'password' => 'required|min:8',
            'verification_code' => 'required|digits:6',
        ]);

        $user = User::where('email',$request->email)->first();

        if(!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

        if($user->email != $request->email)
        {
            return response()->json(['error' => 'Invalid Verification Code'], 401);
        }
        $user->password=bcrypt($request->password);
        $user->verification_code=null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully'], 200);
    }
}
