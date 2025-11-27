<?php

namespace App\Http\Middleware;

use App\Enum\UserTypesEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Check_admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user() && Auth::user()->role_id === 1) {
            return $next($request);
        }
        else
        {
          /*  Auth::logout(); */

            if($request->user() && $request->user()->currentAccessToken())
            {
                $request->user()->currentAccessToken()->delete();
            }
        }
        return response()->json(['message' => 'You are not authorized for this action'], Response::HTTP_UNAUTHORIZED);
    }
}
