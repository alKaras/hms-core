<?php

namespace App\Http\Controllers;

use Exception;
use Stripe\Stripe;
use App\Models\Cart;
use App\Models\User;
use App\Models\Order;
use Stripe\StripeClient;
use App\Models\HServices;
use App\Models\TimeSlots;
use Stripe\PaymentIntent;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use App\Models\OrderPaymentLog;
use Illuminate\Support\Facades\Log;
use Stripe\Service\Climate\OrderService;
use App\Http\Resources\OrderServiceResource;

class OrderController extends Controller
{
    private const ORDER_PAYMENT_COMMISSION = 0.1;
    /**
     * Order checkout method
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function checkout(Request $request)
    {
        $user = auth()->user();
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $cart = Cart::where("user_id", $user->id)->with('items')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }

        $sumPrice = $cart->items->sum('price');
        $totalAmount = $sumPrice + $sumPrice * self::ORDER_PAYMENT_COMMISSION;
        $subtotalAmount = $cart->items->sum('price');

        $order = Order::create([
            'user_id' => $user->id,
            'sum_total' => $totalAmount,
            'sum_subtotal' => $subtotalAmount,
            'status' => 1, //pending
            'created_at' => now(),
            'reserve_exp' => now()->addMinutes(15),
            'updated_at' => now(),
        ]);

        $lineItems = [];

        foreach ($cart->items as $item) {
            $order->orderServices()->create([
                'time_slot_id' => $item->time_slot_id,
                'price' => $item->price,
                'fee' => $item->price * self::ORDER_PAYMENT_COMMISSION,
            ]);
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'uah',
                    'product_data' => [
                        'name' => TimeSlots::find($item->time_slot_id)->service->name,
                    ],
                    'unit_amount' => (TimeSlots::find($item->time_slot_id)->price + TimeSlots::find($item->time_slot_id)->price * self::ORDER_PAYMENT_COMMISSION) * 100,
                ],
                'quantity' => 1,
            ];
        }

        if ($totalAmount > 0) {
            $session = \Stripe\Checkout\Session::create([
                'customer' => User::find($user->id)->stripe_customer_id,
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => env("REACT_APP_URL") . "/checkout/payment/success?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => env("REACT_APP_URL") . "/checkout/payment/cancel?session_id={CHECKOUT_SESSION_ID}",
            ]);

            if ($order) {
                $orderPayment = OrderPayment::create([
                    'order_id' => $order->id,
                    'session_id' => $session->id,
                ]);

                if ($orderPayment) {
                    OrderPaymentLog::create([
                        'order_payment_id' => $orderPayment->id,
                        'event' => 'payment_created',
                        'attributes' => '{}'
                    ]);
                }
            }


            $cart->items()->delete();
            $cart->delete();

            return response()->json([
                'session_id' => $session->id,
                'session_url' => $session->url,
                'customer' => $session->customer,
                'line_items' => $lineItems
            ]);
        } else {
            $cart->items()->delete();
            $cart->delete();

            $order->update([
                'confirmed_at' => now(),
                'status' => 2,
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'success_url' => env("REACT_APP_URL") . "/checkout/payment/success?order_id={$order->id}",
                'order' => $order,
                'line_items' => $lineItems
            ]);
        }
    }

    /**
     * Stripe webhook handler
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function stripeHookHandler(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            return response()->json([
                'message' => 'Invalid payload'
            ], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json([
                'message' => 'Invalid signature'
            ], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $orderPayment = OrderPayment::where('session_id', $session->id)->first();

                if ($orderPayment) {
                    $order = Order::find($orderPayment->order_id);

                    $order->update([
                        'status' => 2,
                        'confirmed_at' => now(),
                        'updated_at' => now(),
                    ]);

                    OrderPaymentLog::create([
                        'order_payment_id' => $orderPayment->id,
                        'event' => 'payment_success',
                        'attributes' => json_encode($session) ?? json_encode('[]'),
                    ]);

                    $orderPayment->update([
                        'payment_id' => $session->payment_intent,
                        'updated_at' => now(),
                    ]);
                }
                //Send notification to the User email
                break;

            default:
                // Unexpected event type
                return response()->json(['message' => 'Unhandled event type'], 400);
        }
        return response()->json(['message' => 'Webhook handled successfully'], 200);

    }

    /**
     * Cancel order method | Cancel order when user navigate to checkout/payment/cancel
     */
    public function cancel(Request $request)
    {
        $orderPayment = OrderPayment::where('session_id', $request->session_id)->first();
        $order = Order::find($orderPayment->order->id);
        if (!$order) {
            return response()->json([
                'status' => 'failure',
                'message' => 'There is no orders by provided id',
            ], 404);
        }

        if ($order->status == 'pending' && $order->confirmed_at === null) {
            $order->update([
                'status' => 3,
                'cancelled_at' => now(),
                'cancel_reason' => 'Canceled by user',
            ]);

            $order->orderServices()->update([
                'is_canceled' => 1,
                'updated_at' => now(),
            ]);

            $order->orderPayments()->create([
                'order_payment_id' => $orderPayment->id,
                'event' => 'payment_canceled',
                'attributes' => json_encode('{"code": "payment_declined", "status": "failure", "err_description": "Failed to proceed payment. Check your parameters"}'),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Order canceled successfully',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    /**
     * Get Order Services collection
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getOrderServices()
    {
        $orderService = OrderService::all();
        return OrderServiceResource::collection($orderService);
    }

    /**
     * Get Order by session method 
     * @param mixed $sessionId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderBySession($sessionId)
    {
        $order = OrderPayment::whereColumn('session_id', $sessionId)->first();
        if ($order) {
            return response()->json([
                'status' => 'success',
                'data' => $order,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No orders by provided session'
            ], 404);
        }
    }
}
