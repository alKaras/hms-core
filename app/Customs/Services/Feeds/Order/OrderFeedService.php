<?php

namespace App\Customs\Services\Feeds\Order;

use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderServiceResource;
use App\Models\Doctor\Doctor;
use App\Models\Hospital\Hospital;

class OrderFeedService
{

    public function __construct(
        private OrderQueryService $orderQueryService,
        private OrderResponderService $orderResponderService
    ) {
    }
    /**
     * Responder for filtering by orderId
     * @param mixed $order_id
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOrderDataByOrderId($order_id)
    {
        $order = $this->orderQueryService->findOrderById($order_id);
        if ($order) {
            $orderServices = $this->orderQueryService->findOrderServices($order->id);
            return $this->orderResponderService->formatOrderWithServices($order, $orderServices);
        } else {
            return $this->orderResponderService->formatError('No orders by provided session', 404);

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

        $order = $this->orderQueryService->findOrderBySession($session_id);

        if ($order) {
            $orderServices = $this->orderQueryService->findOrderServices($order->id);
            return $this->orderResponderService->formatOrderWithServices($order, $orderServices);
        } else {
            return $this->orderResponderService->formatError('No orders by provided session', 404);
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
            $doctorOrders = $this->orderQueryService->findOrderByDoctorId($doctor->id);

            return response()->json([
                'status' => 'ok',
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
            return $this->orderResponderService->formatError('No orders by provided session', 404);
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
        $orders = $this->orderQueryService->findOrderByUserId($user_id, $limit, $perPage, $page, $onlySold);
        // $orders = $limit ? $query->limit($limit)->orderBy('confirmed_at', 'desc')->get() : $query->orderBy('confirmed_at', 'desc')->paginate($perPage);

        if ($orders->isNotEmpty()) {

            return response()->json([
                'status' => 'ok',
                'data' => $orders->map(function ($order) {
                    $orderServices = $this->orderQueryService->findOrderServices($order->id);
                    return [
                        'order' => new OrderResource($order),
                        'services' => OrderServiceResource::collection($orderServices),
                    ];
                }),
                'meta' => $limit === null ? [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ] : null,
                'links' => $limit === null ? [
                    'first' => $orders->url(1),
                    'last' => $orders->url($orders->lastPage()),
                    'prev' => $orders->previousPageUrl(),
                    'next' => $orders->nextPageUrl(),
                ] : null
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
            $hospitalOrders = $this->orderQueryService->findOrderByHospitalId($hospital->id, $perPage, $page);

            $hospitalOrders->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->orderId,
                    'paid_status' => $order->paidStatus,
                    'payment_id' => $order->paymentId,
                    'serviceData' => json_decode($order->services, true),
                ];
            });

            return $this->orderResponderService->formatPaginatedOrders($hospitalOrders);

        } else {
            return $this->orderResponderService->formatError("No data for provided hospitalId #{$hospitalId}");
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
        $query = $this->orderQueryService->findOrderOperationFeed($hospitalId, $perPage, $page, $criteriaCondition);

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

        return $this->orderResponderService->formatPaginatedOrders($query);

    }
}
