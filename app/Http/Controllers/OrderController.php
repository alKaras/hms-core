<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $user = auth()->user();

        $cart = Cart::where("user_id", $user->id)->with('items')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }

        $totalAmount = $cart->items->sum('price');

        $order = Order::create([
            'user_id' => $user->id,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        foreach ($cart->items as $item) {
            $order->orderServices()->create([
                'time_slot_id' => $item->time_slot_id,
                'price' => $item->price,
            ]);
        }

        $cart->items()->delete();
        $cart->delete();

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $totalAmount * 100,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
            ]);

            return response()->json([
                'message' => 'Order created successfully. Waiting for payment confirmation',
                'paymentIntent' => $paymentIntent,
                'order_id' => $order->id,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Checkout failed  ' . $e->getMessage()], 500);
        }

    }

    /**
     * /order/confirm-payment
     */
    public function confirm(Request $request)
    {
        $user = auth()->user();
        $order = Order::where('id', $request->order_id)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
            ], 404);
        }

        if ($order->status === 'paid') {
            return response()->json([
                'message' => 'Order has already paid'
            ], 400);
        }

        $order->update([
            'status' => 'paid',
            'updated_at' => now(),
        ]);
        $order->save();
        return response()->json(['message' => 'Payment confirmed successfully'], 200);
    }
}
