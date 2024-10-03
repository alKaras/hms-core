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
                'data' => [
                    'payment_int_id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount,
                    'canceled_at' => $paymentIntent->canceled_at,
                    'cancellation_reason' => $paymentIntent->cancellation_reason,
                    'client_secret' => $paymentIntent->client_secret,
                    'created' => $paymentIntent->created,
                ],
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

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $paymentIntent = PaymentIntent::update($request->input('payment_intent_id'), ['payment_method' => $request->input('payment_method_id')]);
            if ($paymentIntent->status === 'succeeded') {
                $order->update([
                    'status' => 'paid',
                    'confirmed_at' => now(),
                ]);
                $order->save();

                return response()->json(['message' => 'Payment confirmed successfully'], 200);
            } else {
                return response()->json(['message' => "Payment not completed yet. Current status: {$paymentIntent->status}"], 400);
            }


        } catch (\Exception $e) {
            return response()->json(['message' => 'Error confirming payment: ' . $e->getMessage()], 500);
        }
    }
}
