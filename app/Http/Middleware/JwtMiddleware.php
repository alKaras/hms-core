<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            Auth::setUser($user);
        } catch (TokenExpiredException $e) {

            return response()->json([
                'error' => 'Token Expired!',
                'statusCode' => (int) 401
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'error' => 'Not Authorized!',
                'statusCode' => (int) 401
            ], 401);

        } catch (Exception $e) {
            return response()->json(['error' => 'Authorization Token not found'], 401);
        }

        return $next($request);
    }
}
