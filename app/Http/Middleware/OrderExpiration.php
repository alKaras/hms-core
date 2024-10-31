<?php

namespace App\Http\Middleware;

use App\Enums\TimeslotStateEnum;
use Closure;
use Carbon\Carbon;
use App\Models\TimeSlots;
use App\Models\Order\Order;
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

        $order = Order::where('user_id', value: $user->id)
            ->where('status', value: 1)->first();

        if ($order && $order->confirmed_at == null && $order->status === 1 && $order->reserve_exp < Carbon::now()) {
            $order->update([
                'cancelled_at' => Carbon::now(),
                'status' => 3,
                'cancel_reason' => 'Order Expired',
            ]);

            foreach ($order->orderServices as $service) {
                $timeSlot = TimeSlots::find($service->time_slot_id);
                if ($timeSlot) {
                    $timeSlot->state = TimeslotStateEnum::FREE;
                    $timeSlot->save();
                }
            }

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
