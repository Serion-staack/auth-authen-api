<?php


namespace App\Http\Controllers\API;

use App\Enum\UserTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequestUser;
use App\Http\Requests\RegisterRequestUser;
use App\Models\Refresh_token;
use App\Models\User;

use App\Notifications\OTPMail;
use Carbon\Carbon;
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


class Authen_auth_api_Controller extends Controller
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
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","notes","address","phone_number","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="notes", type="string", example="Some notes about the user"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890"),
     *            @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */


    public function register(RegisterRequestUser $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'notes' =>$validated['notes'],
            'role_id' => $validated['role_id'],
            'user_type' => UserTypesEnum::ADMIN,
            'address' => $validated['address'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'user' => $user,
            'message' => 'User registered successfully'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user and get tokens",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
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
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="token_type", type="string"),
     *             @OA\Property(property="expires_in", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
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
          throw new \Exception('User not found');
        }
        else {
            Log::info('Email exists', ['email' => $request->email]);
        }
        if (!Hash::check($request->password, $user->password))
        {
            $user_Agent=$request->userAgent();
            $ip=$request->header('X-Forwarded-For') ?? $request->ip();
            $location=Http::get("https://ipinfo.io/{$ip}/json/")->json();
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

       /* if (!$user->hasVerifiedEmail()) {
            Log::warning('Login failed: email not verified', ['email' => $request->email]);
            return response()->json([
                'message' => 'Email not verified',
                'resend_verification'=>route('verification_send')
            ], 403);
        }*/
        Log::info('Login successful', ['email' => $request->email]);
        $token = $user->createToken('auth_token')->plainTextToken;
       /* $accessToken = $user->createToken('access_token', ['*'], now()->addHour())->plainTextToken;*/

        $tokenModel = $user->tokens()->latest()->first();
        $tokenModel->expires_at = now()->addMinutes(1);
        $tokenModel->save();


        $refreshToken = Str::random(64);
        Refresh_token::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->addMinutes(3),
        ]);


        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
            'expires_in' => 3600,
            'user_id' => $user->id,

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
        $user->tokens()->delete();
        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $tokenModel = $user->tokens()->latest()->first();
        $tokenModel->expires_at = now()->addMinutes(1);
        $tokenModel->save();

        $newRefresh = Str::random(64);
        Refresh_token::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $newRefresh),
            'expires_at' => now()->addMinutes(3),
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
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'address' => $user->address,
            'notes' => $user->notes,
            'role_id' => $user->role_id,
            'user_type' => $user->user_type,
            'created_at' => $user->created_at,
        ]);
    }


}
