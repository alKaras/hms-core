<?php

namespace App\Http\Middleware;

use App\Models\Cart\Cart;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart && $cart->expired_at < Carbon::now()) {
            $cart->items()->delete();
            $cart->delete();

            return response()->json(['message' => 'Cart has expired'], 410);
        }
        return $next($request);

    }
}
