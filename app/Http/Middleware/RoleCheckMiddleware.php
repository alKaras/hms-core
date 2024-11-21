<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use App\Models\User\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {

        $authUser = auth()->user();

        if (!$authUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $highestPriorityRole = $authUser->roles()->orderBy('priority', 'desc')->pluck('title')->first();



        if (!in_array($highestPriorityRole, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access Forbidden'
            ], 403);
        }


        return $next($request);
    }
}
