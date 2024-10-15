<?php

namespace App\Http\Controllers;

use App\Models\Doctor\Doctor;
use DB;
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
use App\Models\OrderServices;
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
        if ($order->status === 1 && $order->confirmed_at === null) {
            $order->update([
                'status' => 3,
                'cancelled_at' => now(),
                'cancel_reason' => 'Canceled by user',
            ]);

            $order->orderServices()->update([
                'is_canceled' => 1,
                'updated_at' => now(),
            ]);

            $orderPayment->paymentLogs()->create([
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
        $orderService = OrderServices::all();
        return OrderServiceResource::collection($orderService);
    }

    /**
     * Get Order by session method 
     * @param mixed $sessionId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderByFilter(Request $request)
    {

        if ($request->session_id !== null) {
            return $this->responseBySessionId($request->session_id);
        } elseif ($request->order_id !== null) {
            return $this->responseByOrderId($request->order_id);
        } elseif ($request->doctor_id !== null) {
            return $this->responseByDoctorId($request->doctor_id);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Something Went Wrong'
            ], 500);
        }
    }

    /**
     * Responder for filtering by orderId
     * @param mixed $order_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function responseByOrderId($order_id)
    {
        $order = Order::find(id: $order_id);
        if ($order) {
            return response()->json([
                'status' => 'success',
                'data' => $order,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No orders by provided id'
            ], 404);
        }
    }

    /**
     * Responder for filtering by SessionId
     * @param mixed $session_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function responseBySessionId($session_id)
    {
        //Searching by session_id
        $orderPayment = OrderPayment::where(column: 'session_id', operator: $session_id)->first();
        //Get orderInfo 
        if ($orderPayment) {
            $order = Order::find($orderPayment->order_id);
            $orderServices = OrderServices::where('order_id', $order->id)->get();

            return response()->json([
                "order" => [
                    "id" => $order->id,
                    "sum_total" => $order->sum_total,
                    "sum_subtotal" => $order->sum_subtotal,
                    "created_at" => $order->created_at,
                    "confirmed_at" => $order->confirmed_at,
                    "status" => $order->status === 2 ? 'SOLD' : ($order->status === 1 ? 'PENDING' : 'CANCELED'),
                    "reserve_exp" => $order->reserve_exp,
                    "cancelled_at" => $order->cancelled_at,
                    "cancel_reason" => $order->cancel_reason,
                ],
                "order_services" => OrderServiceResource::collection(resource: $orderServices),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No orders by provided session'
            ], 404);
        }
    }

    private function responseByDoctorId($doctor_id)
    {
        $doctor = Doctor::find($doctor_id);
        if ($doctor) {
            $doctorOrders = DB::table('orders as o')
                ->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')
                ->leftJoin('order_payment_logs as opl', 'opl.order_payment_id', '=', 'op.id')
                ->leftJoin('order_status_ref as osr', 'osr.id', '=', 'o.status')
                ->leftJoin('order_services as os', 'os.order_id', '=', 'o.id')
                ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
                ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
                ->leftJoin('department_content as dc', 'dc.department_id', '=', 's.department_id')
                ->where('o.status', 2)
                ->where('ts.doctor_id', $doctor->id)
                ->groupBy('o.id', 'osr.status_name', 'op.payment_id')
                ->selectRaw("
                o.id as orderId,
                osr.status_name as paidStatus,
                JSON_ARRAYAGG(JSON_OBJECT('serviceName', s.name, 'departmentTitle', dc.title, 'startTime', ts.start_time)) as services,
                op.payment_id as paymentId
            ")
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => collect($doctorOrders)->map(function ($order) {
                    return [
                        'id' => $order->orderId,
                        'paid_status' => $order->paidStatus,
                        'payment_id' => $order->paymentId,
                        'serviceData' => json_decode($order->services, true),
                    ];
                })
            ]);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => "No data for provided doctor_id #{$doctor_id}"
            ], 404);
        }
    }
}
