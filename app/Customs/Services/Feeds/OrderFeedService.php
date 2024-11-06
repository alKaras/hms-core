<?php

namespace App\Customs\Services\Feeds;

use App\Customs\Services\CriteriaCondition\CriteriaConditionService;
use App\Models\Order\Order;
use App\Models\Hospital\Hospital;
use App\Models\Order\OrderPayment;
use Illuminate\Support\Facades\DB;
use App\Models\Order\OrderServices;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderServiceResource;

class OrderFeedService
{

    public function __construct(public CriteriaConditionService $criteriaConditionService)
    {
    }
    /**
     * Responder for filtering by orderId
     * @param mixed $order_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderDataByOrderId($order_id)
    {
        $order = Order::find(id: $order_id);
        if ($order) {
            $orderServices = OrderServices::where('order_id', $order->id)->get();
            return response()->json([
                "status" => "success",
                "order" => new OrderResource(resource: $order),
                "order_services" => OrderServiceResource::collection(resource: $orderServices),
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
    public function getOrderDataBySessionId($session_id)
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
    public function getOrderDataByDoctorId($doctor_id)
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
    public function getOrderDataByUserId($user_id, $limit = null, $perPage, $page, $onlySold = false)
    {
        $query = $onlySold ? Order::where('user_id', '=', $user_id)->where('status', '=', '2')
            :
            Order::where('user_id', '=', $user_id);
        $orders = $limit ? $query->limit($limit)->get() : $query->paginate($perPage);

        if (!empty($orders)) {
            return response()->json([
                'status' => 'success',
                'data' => $orders->map(function ($order) {
                    $orderServices = OrderServices::where('order_id', '=', $order->id)->get();
                    return [
                        'order' => new OrderResource($order),
                        'services' => OrderServiceResource::collection(resource: $orderServices),
                    ];
                }),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
                'links' => [
                    'first' => $orders->url(1),
                    'last' => $orders->url($orders->lastPage()),
                    'prev' => $orders->previousPageUrl(),
                    'next' => $orders->nextPageUrl(),
                ]
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
    public function getOrderDataByHospitalId($hospitalId, $perPage, $page)
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
                ->paginate($perPage, ["*"], "page", $page);
            // ->get();

            $hospitalOrders->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->orderId,
                    'paid_status' => $order->paidStatus,
                    'payment_id' => $order->paymentId,
                    'serviceData' => json_decode($order->services, true),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $hospitalOrders->items(),
                'meta' => [
                    'current_page' => $hospitalOrders->currentPage(),
                    'per_page' => $hospitalOrders->perPage(),
                    'total' => $hospitalOrders->total(),
                    'last_page' => $hospitalOrders->lastPage(),
                ],
                'links' => [
                    'first' => $hospitalOrders->url(1),
                    'last' => $hospitalOrders->url($hospitalOrders->lastPage()),
                    'prev' => $hospitalOrders->previousPageUrl(),
                    'next' => $hospitalOrders->nextPageUrl(),
                ]

            ]);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => "No data for provided hospitalId #{$hospitalId}"
            ], 404);
        }
    }

    /**
     * getOrderOperationsFeed method
     * @param mixed $hospitalId
     * @param mixed $perPage
     * @param mixed $page
     * @param mixed $criteriaCondition
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderOperationsFeed($hospitalId = null, $perPage, $page, $criteriaCondition = [])
    {
        $query = DB::table('orders as o')
            ->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')
            ->leftJoin('order_services as os', 'o.id', '=', 'os.order_id')
            ->leftJoin('users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            ->leftJoin('hospital_services as hs', 'hs.service_id', '=', 's.id')
            ->leftJoin('hospital as h', 'h.id', '=', 'hs.hospital_id')
            ->leftJoin('hospital_content as hc', 'hc.hospital_id', '=', 'h.id')
            ->when($hospitalId, function ($query) use ($hospitalId) {
                return $query->whereNotNull('hc.hospital_id');
            })
            ->when($criteriaCondition, function ($query) use ($criteriaCondition) {
                return $this->criteriaConditionService->applyConditions($query, $criteriaCondition);
            })
            ->groupBy('o.id', 'hc.hospital_id', 'hc.title', 'clientName', 'u.phone', 'u.email', 'o.sum_total', 'o.sum_subtotal', 'o.created_at', 'o.confirmed_at', 'o.cancelled_at', 'o.cancel_reason', 'op.payment_id', 'hc.address', 'h.hospital_email', 'h.hospital_phone')
            ->selectRaw("
                o.id as `orderId`,
                hc.hospital_id as `hospitalId`,
                hc.title as `hospitalTitle`,
                hc.address as `hospitalAddress`,
                h.hospital_email as `hospitalEmail`,
                h.hospital_phone as `hospitalPhone`,
                CASE
                    WHEN o.status = 1 THEN 'PENDING'
                    WHEN o.status = 2 THEN 'SOLD'
                    WHEN o.status = 3 THEN 'CANCELED'
                    ELSE ''
                END AS `paidStatus`,
                CONCAT(u.name, ' ', u.surname) AS `clientName`,
                u.phone AS `phone`,
                u.email AS `email`,
                COUNT(os.id) AS `serviceQuantity`,
                o.sum_total AS `total`,
                o.sum_subtotal AS `subtotal`,
                o.created_at AS `dateCreated`,
                o.confirmed_at AS `dateConfirmed`,
                IF(o.status = 1, o.reserve_exp, NULL) AS `reserveExpiration`,
                o.cancelled_at AS `canceledAt`,
                o.cancel_reason AS `cancelReason`,
                op.payment_id AS `paymentId`
            ")
            ->paginate($perPage, ["*"], "page", $page);

        $query->getCollection()->transform(function ($order) {
            return [
                'orderId' => $order->orderId,
                'hospital_id' => $order->hospitalId,
                'hospital_title' => $order->hospitalTitle,
                'hospital_address' => $order->hospitalAddress,
                'hospital_email' => $order->hospitalEmail,
                'hospital_phone' => $order->hospitalPhone,
                'client_name' => $order->clientName,
                'client_phone' => $order->phone,
                'client_email' => $order->email,
                'service_quantity' => $order->serviceQuantity,
                'paid_total' => $order->total,
                'paid_subtotal' => $order->subtotal,
                'date_created' => $order->dateCreated,
                'date_confirmed' => $order->dateConfirmed,
                'paid_status' => $order->paidStatus,
                'payment_id' => $order->paymentId,
                'reserve_expiration' => $order->reserveExpiration,
                'canceled_at' => $order->canceledAt,
                'cancel_reason' => $order->cancelReason,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $query->items(),
            'meta' => [
                'current_page' => $query->currentPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
                'last_page' => $query->lastPage(),
            ],
            'links' => [
                'first' => $query->url(1),
                'last' => $query->url($query->lastPage()),
                'prev' => $query->previousPageUrl(),
                'next' => $query->nextPageUrl(),
            ]
        ]);

    }
}