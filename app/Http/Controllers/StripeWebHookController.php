<?php

namespace App\Http\Controllers;

use Stripe\Event;
use App\Models\Order;
use Illuminate\Http\Request;

class StripeWebHookController extends Controller
{
    public function handleStripeWebHook(Request $request)
    {
        $payload = $request->getContent();

        $event = null;

        try {
            $event = Event::constructFrom(
                json_decode($payload, true)
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        if ($event->type == 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            $order = Order::where('stripe.payment_id', $paymentIntent->id)->first();

        }
    }
}
