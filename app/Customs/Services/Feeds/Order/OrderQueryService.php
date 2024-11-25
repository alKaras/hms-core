<?php

namespace App\Customs\Services\Feeds\Order;

use App\Customs\Services\CriteriaCondition\CriteriaConditionService;
use App\Enums\OrderStatusEnum;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderServices;
use Illuminate\Support\Facades\DB;

class OrderQueryService
{

    public function __construct(
        private CriteriaConditionService $criteriaConditionService
    ) {
    }

    public function findOrderById($order_id)
    {
        return Order::find($order_id);
    }

    public function findOrderServices($order_id)
    {
        return OrderServices::where('order_id', $order_id)->get();
    }

    public function findOrderBySession($session_id)
    {
        $orderPayment = OrderPayment::where(column: 'session_id', operator: $session_id)->first();
        return Order::find($orderPayment->order_id);
    }

    public function findOrderByDoctorId($doctor_id)
    {
        return DB::table('orders as o')
            ->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')
            ->leftJoin('order_payment_logs as opl', 'opl.order_payment_id', '=', 'op.id')
            ->leftJoin('order_status_ref as osr', 'osr.id', '=', 'o.status')
            ->leftJoin('order_services as os', 'os.order_id', '=', 'o.id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            ->leftJoin('department_content as dc', 'dc.department_id', '=', 's.department_id')
            ->where('o.status', OrderStatusEnum::SOLD)
            ->where('ts.doctor_id', $doctor_id)
            ->groupBy('o.id', 'osr.status_name', 'op.payment_id')
            ->selectRaw("
                o.id as orderId,
                osr.status_name as paidStatus,
                JSON_ARRAYAGG(JSON_OBJECT('serviceName', s.name, 'departmentTitle', dc.title, 'startTime', ts.start_time)) as services,
                op.payment_id as paymentId
            ")
            ->get();
    }

    public function findOrderByUserId($user_id, $limit, $perPage, $page, $onlySold)
    {
        $query = $onlySold ? Order::where('user_id', '=', $user_id)->where('status', '=', OrderStatusEnum::SOLD)
            :
            Order::where('user_id', '=', $user_id);
        $orders = $query->with(['orderServices.timeSlot'])
            ->whereHas('orderServices.timeSlot', function ($q) {
                $q->orderBy('start_time', 'desc');
            });

        if ($limit) {
            $orders = $orders->limit($limit)->get();
        } else {
            $orders = $orders->paginate($perPage);
        }
        return $orders;
    }

    public function findOrderByHospitalId($hospitalId, $perPage, $page)
    {
        return DB::table('orders as o')
            ->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')
            ->leftJoin('order_payment_logs as opl', 'opl.order_payment_id', '=', 'op.id')
            ->leftJoin('order_status_ref as osr', 'osr.id', '=', 'o.status')
            ->leftJoin('order_services as os', 'os.order_id', '=', 'o.id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            ->leftJoin('department_content as dc', 'dc.department_id', '=', 's.department_id')
            ->where('o.status', OrderStatusEnum::SOLD)
            ->where('o.hospital_id', $hospitalId)
            ->groupBy('o.id', 'osr.status_name', 'op.payment_id')
            ->selectRaw("
                o.id as orderId,
                osr.status_name as paidStatus,
                JSON_ARRAYAGG(JSON_OBJECT('serviceName', s.name, 'departmentTitle', dc.title, 'startTime', ts.start_time)) as services,
                op.payment_id as paymentId
            ")
            ->paginate($perPage, ["*"], "page", $page);
    }

    public function findOrderOperationFeed($hospitalId, $perPage, $page, $criteriaCondition)
    {
        return DB::table('orders as o')
            ->leftJoin('order_payments as op', 'op.order_id', '=', 'o.id')
            ->leftJoin('order_services as os', 'o.id', '=', 'os.order_id')
            ->leftJoin('users as u', 'u.id', '=', 'o.user_id')
            ->leftJoin('time_slots as ts', 'ts.id', '=', 'os.time_slot_id')
            ->leftJoin('services as s', 's.id', '=', 'ts.service_id')
            // ->leftJoin('hospital_services as hs', 'hs.service_id', '=', 's.id')
            ->leftJoin('hospital as h', 'h.id', '=', 'o.hospital_id')
            ->leftJoin('hospital_content as hc', 'hc.hospital_id', '=', 'h.id')
            ->when($hospitalId, function ($query) use ($hospitalId) {
                return $query->where('o.hospital_id', '=', $hospitalId);
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
    }


}
