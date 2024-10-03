<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        $order = Order::where('user_id', $user->id)->first();

        if ($order && $order->reserve_exp < Carbon::now()) {
            $order->update([
                'cancelled_at' => Carbon::now(),
                'status' => 'canceled',
                'cancel_reason' => 'Order Expired',
            ]);
            $order->orderServices()->update([
                'is_canceled' => 1,
            ]);
            $order->save();

            return response()->json([
                'message' => 'Order Expired'
            ], 410);
        }

        return $next($request);
    }
}
