<?php


namespace App\Http\Controllers\API;

use App\Enum\UserTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequestUser;
use App\Http\Requests\RegisterRequestUser;
use App\Models\Refresh_token;
use App\Models\User;
use App\Notifications\LoginMail;
use App\Notifications\OTPMail;
use App\Notifications\VerifyEmailNotification;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Auth & Authen API",
 *     description="API documentation for Authentication endpoints",
 *     @OA\Contact(
 *         email="your-email@example.com"
 *     )
 * )
 */


class AuthorizationController extends Controller
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
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Registers a new user and sends an email verification link.",
     *     operationId="registerUser",
     *     tags={"Auth"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name","email","address","phone_number","role_id","password","password_confirmation"},
     *             @OA\Property(property="full_name", type="string", example="JohnDoe", description="Alphanumeric username (no spaces or special chars)"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Unique user email"),
     *             @OA\Property(property="notes", type="string", nullable=true, example="This is an optional note about the user"),
     *             @OA\Property(property="address", type="string", example="123 Main St, City, State", description="User address, supports letters, numbers, commas, and dots"),
     *             @OA\Property(property="phone_number", type="string", example="+12345678901", description="Valid phone number (8–15 digits, may start with +)"),
     *             @OA\Property(property="role_id", type="integer", example=2, description="Existing role ID (foreign key to roles table)"),
     *             @OA\Property(property="password", type="string", format="password", example="StrongP@ssword123", description="Min 12 chars, must include uppercase, lowercase, number, and special character"),
     *             @OA\Property(property="password_confirmation", type="string", example="StrongP@ssword123", description="Password confirmation"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="full_name", type="string", example="JohnDoe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="role_id", type="integer", example=2),
     *                 @OA\Property(property="user_type", type="string", example="ADMIN"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="phone_number", type="string", example="+12345678901"),
     *                 @OA\Property(property="notes", type="string", example="Optional note")
     *             ),
     *             @OA\Property(property="message", type="string", example="User registered successfully. Check email for verification code")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */



    public function register(Request $request)
{
    $request->validate([
        'full_name' => ['required', 'string', 'max:255','regex:/^[\pL\pN ]+$/u', 'not_in:password,name,email'],
        'email' => ['required', 'email', 'unique:users,email'],
        'notes' => ['nullable', 'string', 'max:500'],
        'address' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s,\.#-]+$/'],
        'phone_number' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
        'role_id' => ['required', 'integer', 'exists:roles,id'],
        'password' => [
            'required', 'string', 'min:12', 'confirmed',
            'regex:/[0-9]/', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[@$!%*#?&]/',
        ],
    ]);

    $user = User::create([
        'full_name' => $request->full_name,
        'email' => $request->email,
        'notes' =>$request->notes,
        'role_id' => $request->role_id,
        'user_type' => UserTypesEnum::ADMIN,
        'address' => $request->address,
        'phone_number' => $request->phone_number,
        'password' => Hash::make($request->password),
    ]);

    $user->notify(new VerifyEmailNotification());


    return response()->json([
        'user' => $user,
        'message' => 'User registered successfully.Check email for verification link'], 201);
}

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authenticate user and send 2FA code via email",
     *     description="Validates user credentials. If valid, sends a 6-digit verification code to the user's email for 2FA.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful, verification code sent to email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
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
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required."))
     *             )
     *         )
     *     )
     * )
     */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if ($user == null) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        else {
            Log::info('Email exists', ['email' => $request->email]);
        }
        if (!Hash::check($request->password, $user->password))
        {
            Log::warning('Wrong password',['email' => $request->email]);
            $user_Agent=$request->userAgent();
            $ip=$request->ip();
            $location=Http::timeout(5)->get("https://ipinfo.io/{$ip}/json/")->json();

            Log::info('Api location',$location);
            try
            {
                $country_name=$location['country_name'] ?? null;
                $country_code=$location['country_code'] ?? null;
                $city=$location['city'] ?? null;
                $region=$location['region'] ?? null;
                $postal_code=$location['postal_code'] ?? null;
                $latitude=$location['latitude'] ?? null;
                $longitude=$location['longitude'] ?? null;
                $region_code=$location['region_code'] ?? null;
            }
            catch (\Exception $e)
            {
                $country_code=null;
                $country_name=null;
                $region_code=null;
                $region=null;
                $city=null;
                $postal_code=null;
                $latitude=null;
                $longitude=null;
            }
            $agent=new Agent();
             $agent->setUserAgent($user_Agent);

             if($agent->isMobile())
             {
                 $device_type='Mobile';
             }
             elseif ($agent->isTablet())
             {
                 $device_type='Tablet';
             }
             else
             {
                 $device_type='Desktop';
             }

            $deviceName = $agent->device();
            $os = $agent->platform();
            $browser = $agent->browser();
            $osVersion = $agent->version($os);
            $browserVersion = $agent->version($browser);
            $created_at=Carbon::now() ?? '--' ;


            Log::warning('Login failed: invalid password',
                [
                    'email' => $request->email,
                    'ip' => $ip,
                    'device_type' => $device_type,
                    'country_name' => $country_name,
                    'country_code' => $country_code,
                    'city'=>$city,
                    'user_agent' => $user_Agent,
                    'osVersion' =>$os . ' ' . $osVersion,
                    'browserVersion'=>$browserVersion,
                    'browser' => $browser,
                    'device' => $deviceName,
                    'created_at' => $created_at,
                    'region'=>$region,
                    'postal_code'=>$postal_code,
                    'latitude'=>$latitude,
                    'longitude'=>$longitude,
                    'region_code'=>$region_code,
                ]);
            return response()->json(['message' => 'Invalid password'], 401);
        }
        else {
            Log::info('Password correct', ['email' => $request->email]);
        }

        if (!$user->hasVerifiedEmail()) {
            Log::warning('Login failed: email not verified', ['email' => $request->email]);
            return response()->json([
                'message' => 'Email not verified',
               /*'resend_verification'=>route('verification.resend')*/
                'verification' => false,
            ], 403);
        }

        Log::info('Login successful', ['email' => $request->email]);

        $login_code=rand(100000,999999);
        $user->login_code=Hash::make($login_code);
        $user->login_code_expires_at=now()->addMinutes(2);
        $user->save();

        $user->notify(new LoginMail($login_code));

        return response()->json([
            'message' => 'Please check your email for verification code',
            'email' => $user->email,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-login-code",
     *     summary="Verify 2FA code and generate tokens",
     *     description="Verifies a 6-digit login code sent via email. If valid, returns an access token and refresh token. Supports 'remember me' for extended token lifetime.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "login_code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="login_code", type="integer", example=123456),
     *             @OA\Property(property="remember", type="boolean", example=true, description="Set to true to enable 'Remember Me' (extends refresh token validity to 7 days).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification successful, tokens generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="access_token", type="string", example="1|K2d39sOZ1Fd..."),
     *             @OA\Property(property="refresh_token", type="string", example="zU31saSDF9xA7..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="remember", type="boolean", example=true),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="access_token_expires_at", type="string", format="date-time", example="2025-10-31T15:22:10Z"),
     *             @OA\Property(property="refresh_token_expires_at", type="string", format="date-time", example="2025-11-07T15:22:10Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired login code",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid or expired login code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
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
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required.")),
     *                 @OA\Property(property="login_code", type="array", @OA\Items(type="string", example="The login code field is required."))
     *             )
     *         )
     *     )
     * )
     */


    public function verifyLoginCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'login_code' => 'required|integer',
        ]);

        $user=User::where('email',$request->email)->first();
        if (!$user)
        {
            return response()->json(['message' => 'If this email exists, a code was sent'], 200);
        }

        if(!Hash::check($request->login_code,$user->login_code) || now()->greaterThan($user->login_code_expires_at))
        {
            return response()->json([
                'message' => 'Invalid or expired login code'
            ],401);
        }
        $user->login_code = null;
        $user->login_code_expires_at = null;
        $user->save();

        $remember=$request->boolean('remember');

        $accessTokenExpiry=now()->addMinutes(15);

        $refreshTokenExpiry = $remember ? now()->addDays(7) : now()->addMinutes(20);

        $token = $user->createToken('auth_token')->plainTextToken;

        /* $accessToken = $user->createToken('access_token', ['*'], now()->addHour())->plainTextToken;*/

        $tokenModel = $user->tokens()->latest()->first();
        $tokenModel->expires_at = $accessTokenExpiry;
        $tokenModel->save();


        $refreshToken = Str::random(64);
        Refresh_token::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => $refreshTokenExpiry,
        ]);


        Log::info('User verified successfully', ['email' => $user->email,'remember' => $remember]);

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
            'user_id' => $user->id,
            'remember' => $remember,
            'access_token_expires_at' => $accessTokenExpiry,
            'refresh_token_expires_at' => $refreshTokenExpiry,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     summary="Refresh access token using refresh token",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string", example="your_refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tokens refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=60)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired refresh token"
     *     )
     * )
     */

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $hashed = hash('sha256', $request->refresh_token);
        $stored = Refresh_token::where('token', $hashed)->first();

        if (!$stored || $stored->expires_at->isPast()) {
            if ($stored) $stored->delete();
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }
        $user = $stored->user;
        $stored->delete();
       /* $user->tokens()->delete();*/
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $tokenModel = $user->tokens()->latest()->first();
        $tokenModel->expires_at = now()->addMinutes(15);
        $tokenModel->save();

        $newRefresh = Str::random(64);
        Refresh_token::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $newRefresh),
            'expires_at' => now()->addMinutes(60),
        ]);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $newRefresh,
            'token_type' => 'Bearer',
            'expires_in' => 60,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user and revoke tokens",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        $user->tokens()->delete();
       /* $user->currentAccessToken()->delete();*/

        Refresh_token::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get authenticated user data",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function user(Request $request)
    {
        $user=$request->user();

        return response()->json([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'address' => $user->address,
            'notes' => $user->notes,
            'role_id' => $user->role_id,
            'user_type' => $user->user_type,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/update_user/{id}",
     *     summary="Update the authenticated user's information",
     *     description="Updates the authenticated user's profile information. Requires Bearer token authentication.",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user to update (should match the authenticated user ID)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","address","phone_number","role_id","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="notes", type="string", example="Preferred contact via email"),
     *             @OA\Property(property="address", type="string", example="123 Main St, Springfield"),
     *             @OA\Property(property="phone_number", type="string", example="+12345678901"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="password", type="string", example="StrongPass123!"),
     *             @OA\Property(property="password_confirmation", type="string", example="StrongPass123!")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="address", type="string", example="123 Main St, Springfield"),
     *                 @OA\Property(property="phone_number", type="string", example="+12345678901"),
     *                 @OA\Property(property="notes", type="string", example="Preferred contact via email")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - user not authenticated",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User not found"))
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken."))
     *             )
     *         )
     *     )
     * )
     */

    public function updateUser(Request $request,$id)
    {
       $user=$request->user();

       if(!$user)
       {
           return response()->json(['message' => 'User not found']);
       }
        $validated=$request->validate([
           /* 'full_name'    =>  ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9]+$/', 'not_in:password,name,email'],*/
            'email'        =>  ['sometimes', 'email', 'unique:users,email'],
            'notes'        =>  ['sometimes', 'string', 'max:500'],
            'address'      =>  ['sometimes', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s,\.#-]+$/'],
            'phone_number' =>  ['sometimes', 'regex:/^\+?[0-9]{8,15}$/'],
            'role_id'      =>  ['sometimes', 'integer', 'exists:roles,id'],
            'password'     =>  ['sometimes', 'string', 'min:12', 'confirmed', 'regex:/[0-9]/', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[@$!%*#?&]/',],
        ]);

       if (isset($validated['password'])) {
           $validated['password'] = Hash::make($validated['password']);
       }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'notes'=> $user->notes,
            'address' => $user->address,
            'role_id' => $user->role_id,
            'user_type' => $user->user_type,
            'phone_number' => $user->phone_number,
            'email_verified_at' => $user->email_verified_at,
            'updated_at' => $user->updated_at,

        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/delete_user/{id}",
     *     summary="Delete the authenticated user account",
     *     description="Deletes the authenticated user's account and all associated tokens. Requires Bearer token authentication.",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user to delete (should match the authenticated user ID)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - user not authenticated",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Unauthenticated."))
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="User not found"))
     *     )
     * )
     */

    public function deleteUser(Request $request,$id)
     {
         $user = $request->user();
         if(!$user)
         {
             return response()->json(['message' => 'User not found']);
         }
         $user->tokens()->delete();
         Refresh_token::where('user_id', $user->id)->delete();
         $user->delete();
         return response()->json(['message' => 'User deleted successfully']);
     }

    /**
     * @OA\Get(
     *     path="/api/get_all_users",
     *     summary="Get all users with specific roles",
     *     description="Returns a list of all users who have role_id 1, 2, 3, 4, or 5. Only accessible by admin users (role_id = 1).",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Get all users successfully"),
     *             @OA\Property(property="count", type="integer", example=5),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="full_name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com"),
     *                     @OA\Property(property="phone_number", type="string", example="+355691234567"),
     *                     @OA\Property(property="address", type="string", example="Rruga e Re, Tirane"),
     *                     @OA\Property(property="role_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-27T12:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized (not admin or invalid token)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */

    public function getAllUsers(Request $request)
     {

         $user=User::whereIn('role_id',[1,2,3,4,5])->get(['id','full_name','email','phone_number','address','role_id','created_at']);

         return response()->json([
             'message' => 'Get all users successfully',
             'count' => $user->count(),
             'users' => $user,
         ]);
     }

    /**
     * @OA\Post(
     *     path="/api/reset_login_code",
     *     summary="Resend a new login code to the user's email",
     *     description="Sends a new 6-digit login code if the previous one has expired.",
     *     operationId="resetLoginCode",
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
     *         description="A new login code has been sent to your email",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A new login code has been sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not found or previous code still valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please wait until it expires")
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

    public function resetLoginCode(Request $request)
     {
         $request->validate([
             'email' => 'required|email',
         ]);

         $user=User::where('email',$request->email)->first();

         if(!$user)
         {
             return response()->json(['message' => 'If this email exists, a code was sent.'],200);

         }

         if($user->login_code_expires_at && now()->lessThan($user->login_code_expires_at))
         {
             return response()->json(['message' => 'Please wait before requesting a new code'],429);
         }

         $newCode =rand(100000,999999);

         $user->login_code = Hash::make($newCode);
         $user->login_code_expires_at = now()->addMinutes(5);
         $user->save();

        $user->notify(new LoginMail($newCode));


         return response()->json(['message' => 'A new login code has been sent to your email'], 200);

     }




}
