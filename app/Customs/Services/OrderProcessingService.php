<?php

namespace App\Customs\Services;

use App\Enums\AppointmentsStatusEnum;
use App\Models\MedAppointments;
use App\Models\MedCard;
use App\Models\Order\OrderServices;
use Stripe\Stripe;
use App\Models\Cart\Cart;
use App\Models\TimeSlots;
use App\Models\User\User;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use App\Enums\TimeslotStateEnum;
use App\Models\Hospital\Hospital;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderPaymentLog;
use App\Notifications\TimeSlotConfirmationNotification;

class OrderProcessingService
{
    private const ORDER_PAYMENT_COMMISSION = 0.1;

    public function checkoutProcessing()
    {
        $user = auth()->user();

        $cart = Cart::where("user_id", $user->id)->with('items')->first();

        Stripe::setApiKey(env('STRIPE_SECRET'));
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }

        $sumPrice = $cart->items->sum('price');
        $totalAmount = $sumPrice + $sumPrice * self::ORDER_PAYMENT_COMMISSION;
        $subtotalAmount = $cart->items->sum('price');

        $order = Order::create([
            'user_id' => $user->id,
            'hospital_id' => $cart->hospital_id,
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

            $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::SOLD);

            $this->makeAppointmentOnConfirmation($order);

            $this->sendOrderConfirmationNotification($order);

            return response()->json([
                'status' => 'ok',
                'success_url' => env("REACT_APP_URL") . "/checkout/payment/success?order_id={$order->id}",
                'order' => $order,
                'line_items' => $lineItems
            ]);
        }
    }

    public function stripeHookProcessing(Request $request)
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

                    $this->makeAppointmentOnConfirmation($order);

                    $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::SOLD);

                    OrderPaymentLog::create([
                        'order_payment_id' => $orderPayment->id,
                        'event' => 'payment_success',
                        'attributes' => json_encode($session) ?? json_encode('[]'),
                    ]);

                    $orderPayment->update([
                        'payment_id' => $session->payment_intent,
                        'updated_at' => now(),
                    ]);

                    //Send notification to the User email
                    $this->sendOrderConfirmationNotification($order);
                }

                break;

            default:
                // Unexpected event type
                return response()->json(['message' => 'Unhandled event type'], 400);
        }
        return response()->json(['message' => 'Webhook handled successfully'], 200);
    }

    public function cancelProcessing(Request $request)
    {
        if ($request->session_id) {
            $orderPayment = OrderPayment::where('session_id', $request->session_id)->first();
            $order = Order::find($orderPayment->order->id);
            $checkoutCancellation = true;
        } elseif ($request->order_id) {
            $order = Order::find($request->order_id);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No provided data'
            ], 404);
        }

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no orders by provided id',
            ], 404);
        }

        $order->update([
            'status' => 3,
            'cancelled_at' => now(),
            'cancel_reason' => 'Canceled by user',
        ]);

        $order->orderServices()->update([
            'is_canceled' => 1,
            'updated_at' => now(),
        ]);

        $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::FREE);


        ($order->status === 1 && $order->confirmed_at === null) && $orderPayment->paymentLogs()->create([
            'order_payment_id' => $orderPayment->id,
            'event' => 'payment_canceled',
            'attributes' => json_encode([
                "code" => "payment_declined",
                "status" => "failure",
                "err_description" => "Failed to proceed payment. Check your parameters"
            ]),
            'updated_at' => now(),
        ]);



        return response()->json([
            'status' => 'ok',
            'message' => 'Order canceled successfully',
        ]);
    }

    public function sendConfirmation(Request $request)
    {
        $order = Order::find($request->order_id);

        if ($order) {
            $this->sendOrderConfirmationNotification($order);
            return response()->json([
                'status' => 'ok',
                'message' => 'Order confirmation sent successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no data for provided orderId'
            ]);
        }
    }

    private function changeOrderServicesTimeslotsState(Order $order, TimeslotStateEnum $status): void
    {
        foreach ($order->orderServices as $service) {
            $timeSlot = TimeSlots::find($service->time_slot_id);

            if ($timeSlot) {
                $timeSlot->state = $status;
                $timeSlot->save();
            }
        }
    }

    private function makeAppointmentOnConfirmation(Order $order)
    {
        $user = User::find($order->user_id);
        $medcard = MedCard::where('user_id', '=', $user->id)->first() ?? null;

        $timeslots = $order->orderServices->map(function ($orderService) {
            return $orderService->timeSlot;
        });

        foreach ($timeslots as $slot) {
            $appointment = MedAppointments::create([
                'user_id' => $user->id,
                'doctor_id' => $slot->doctor_id,
                'time_slot_id' => $slot->id,
                'status' => AppointmentsStatusEnum::SCHEDULED,
                'medcard_id' => $medcard->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $appointment->save();
        }
    }

    private function sendOrderConfirmationNotification(Order $order)
    {
        $user = User::find($order->user->id);
        $timeSlots = $order->orderServices->map(function ($orderService) {
            return $orderService->timeSlot; // Map each service to its associated time slot
        });

        $user->notify(new TimeSlotConfirmationNotification($timeSlots));
    }
}