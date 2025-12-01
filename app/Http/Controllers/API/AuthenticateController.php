<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\Refresh_token;
use App\Models\User;
use App\Notifications\OTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;


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



  /*  public function verificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $user = User::where('email',$request->email)->first();

        if(!$user)
        {
            return response()->json(['error' => 'Email not found'], 401);
        }

        if ($user->verification_code_expired_at && now()->lessThan($user->verification_code_expired_at)) {
            return response()->json(['error' => 'A reset code is already active. Please wait until it expires.'], 429);
        }

        $verification_code = rand(100000, 999999);
        $user->verification_code = Hash::make($verification_code);
        $user->verification_code_expired_at = now()->addMinutes(10);


        $user->save();

        $user->notify(new OTPMail($verification_code));

        return response()->json(['message' => 'Password Reset Code sent to your email'], 200);
    }*/   //1



   /* public function verifyCode(Request $request)
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
            return response()->json(['error' => 'No verification code request found'], 401);
        }

        if(now()->greaterThan($user->verification_code_expired_at))
        {
            $user->verification_code = null;

            $user->verification_code_expired_at=null;

            $user->save();

            return response()->json(['error' => 'Your verification code has been expired.'], 200);
        }

        if(!Hash::check($request->verification_code,$user->verification_code))
        {
            return response()->json(['error' => 'Invalid verification code'], 401);
        }

        //I ben null dhe nuk mund te besh dot ndrrimin e passw ,kur nuk je i loguar.
        $user->verification_code = null;
        $user->verification_code_expired_at=null;
        $user->save();

        return response()->json(['message' => 'verification Code has been verified successfully'], 200);
    }*/    //2

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

          $user = $request->user();

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

          $user->tokens()->delete();

          Refresh_token::where('user_id',$user->id)->delete();     //added here

         /*$currentTokenId = $user->currentAccessToken()?->id;

        $user->tokens()->when($currentTokenId, fn($q) => $q->where('id', '!=', $currentTokenId))
        ->delete();*/

          return response()->json(['message' => 'Password Changed succesfully'], 200);
      }



   /* public function resetttPassword(Request $request)
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

        //Duhet komentuar
        if($user->email != $request->email)
        {
            return response()->json(['error' => 'Invalid Verification Code'], 401);
        }

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

        Refresh_token::where('user_id',$user->id)->delete();

        return response()->json(['message' => 'Password reset successfully'], 200);
    }*/

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

        if($user->verification_code_expired_at && now()->lessThan($user->verification_code_expired_at))
        {
            return response()->json(['error' => 'Previous code is still valid,please wait until it expires'], 401);
        }

        $new_code=random_int(100000, 999999);

        $user->verification_code = Hash::make($new_code);

        $user->verification_code_expired_at=now()->addMinutes(4);

        $user->save();

        $user->notify(new OTPMail($new_code));

        return response()->json(['message' => 'A new verification code has been sent to your email'], 200);
    }

}
