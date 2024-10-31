<?php

namespace App\Http\Controllers;

use App\Enums\OrderFiltersEnum;
use App\Enums\TimeslotStateEnum;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderServiceResource;
use App\Models\Cart\Cart;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderPaymentLog;
use App\Models\Order\OrderServices;
use App\Models\TimeSlots;
use App\Models\User\User;
use App\Notifications\TimeSlotConfirmationNotification;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;

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

            $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::SOLD);

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
                    $user = User::find($order->user->id);

                    //Send notification to the User email
                    $this->sendOrderConfirmationNotification($user, $order);
                }

                break;

            default:
                // Unexpected event type
                return response()->json(['message' => 'Unhandled event type'], 400);
        }
        return response()->json(['message' => 'Webhook handled successfully'], 200);

    }

    /**
     * Send confirmation email for order
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function sendOrderConfirmationMail(Request $request)
    {
        $order = Order::find($request->order_id);

        if ($order) {
            $user = User::find($order->user->id);
            $this->sendOrderConfirmationNotification($user, $order);
            return response()->json([
                'status' => 'success',
                'message' => 'Order confirmation sent successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no data for provided orderId'
            ]);
        }
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

            $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::FREE);

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
     * Get Order/s by filter
     * @param Request $request {required filter [string] | session_id | order_id | doctor_id | user_id}
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderByFilter(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'filter' => ['required', 'string'],
            'session_id' => ['exists:order_payments,session_id'],
            'order_id' => ['exists:orders,id'],
            'doctor_id' => ['exists:doctors,id'],
            'user_id' => ['exists:users,id'],
            'hospital_id' => ['exists:hospital,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Check provided data',
                'errors' => $validator->errors()
            ], 422);
        }
        $limit = $request->limit ?? null;
        $filterEnum = OrderFiltersEnum::tryFrom($request->input('filter'));
        $sessionId = $request->input('session_id') ?? null;
        $orderId = $request->input('order_id') ?? null;
        $doctorId = $request->input('doctor_id') ?? null;
        $userId = $request->input('user_id') ?? null;
        $hospitalId = $request->input('hospital_id') ?? null;

        switch ($filterEnum) {
            case OrderFiltersEnum::OrdersById:
                if ($orderId !== null) {
                    return $this->responseByOrderId($orderId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Order Id is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersbySession:
                if ($sessionId !== null) {
                    return $this->responseBySessionId($sessionId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'SessionId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersbyDoctor:
                if ($doctorId !== null) {
                    return $this->responseByDoctorId($doctorId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'DoctorId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersbyUser:
                if ($userId !== null) {
                    return $this->responseByUserId($userId, $limit);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'UserId is required for this filter'
                    ], 500);
                }

            case OrderFiltersEnum::OrdersByHospital:
                if ($hospitalId !== null) {
                    return $this->responseByHospitalId($hospitalId);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'HospitalId is required for this filter'
                    ], 500);
                }

            default:
                return response()->json([
                    'status' => 'error',
                    'error' => 'Provided filter is not recognised'
                ], 400);
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
                "order" => new OrderResource(resource: $order),
                "order_services" => OrderServiceResource::collection(resource: $orderServices),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No orders by provided session'
            ], 404);
        }
    }

    /**
     * Responder for filtering orders by DoctorId
     * @param mixed $doctor_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
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

    /**
     * Responder for filtering orders by UserId
     * @param mixed $user_id
     * @param mixed $limit
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function responseByUserId($user_id, $limit = null)
    {
        $query = Order::where('user_id', '=', $user_id);
        $orders = $limit ? $query->limit($limit)->get() : $query->get();

        if (!empty($orders)) {
            return response()->json([
                'status' => 'success',
                'data' => $orders->map(function ($order) {
                    $orderServices = OrderServices::where('order_id', '=', $order->id)->get();
                    return [
                        'order' => new OrderResource($order),
                        'services' => OrderServiceResource::collection(resource: $orderServices),
                    ];
                })
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'There is no orders for provided user'
            ], 404);
        }
    }

    /**
     * Responder for filtering orders by hospital id
     * @param mixed $hospitalId
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function responseByHospitalId($hospitalId)
    {
        $hospital = Hospital::find($hospitalId);
        if ($hospital) {
            $hospitalOrders = DB::table('orders as o')
                ->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')
                ->leftJoin('order_payment_logs as opl', 'opl.order_payment_id', '=', 'op.id')
                ->leftJoin('order_status_ref as osr', 'osr.id', '=', 'o.status')
                ->leftJoin('order_services as os', 'os.order_id', '=', 'o.id')
                ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
                ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
                ->leftJoin('department_content as dc', 'dc.department_id', '=', 's.department_id')
                ->leftJoin('hospital_departments as hd', 'hd.department_id', '=', 's.department_id')
                ->where('o.status', 2)
                ->where('hd.hospital_id', $hospital->id)
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
                'data' => collect($hospitalOrders)->map(function ($order) {
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
                'message' => "No data for provided doctor_id #{$hospitalId}"
            ], 404);
        }
    }


    /**
     * Send Order Confirmation notification method
     * @param \App\Models\User\User $user
     * @param \App\Models\Order\Order $order
     * @return void
     */
    protected function sendOrderConfirmationNotification(User $user, Order $order)
    {
        $timeSlots = $order->orderServices->map(function ($orderService) {
            return $orderService->timeSlot; // Map each service to its associated time slot
        });

        $user->notify(new TimeSlotConfirmationNotification($timeSlots));
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
}
