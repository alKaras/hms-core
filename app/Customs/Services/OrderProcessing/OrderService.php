<?php

namespace App\Customs\Services\OrderProcessing;

use App\Customs\Services\Feeds\Order\OrderQueryService;
use App\Enums\OrderStatusEnum;
use App\Enums\TimeslotStateEnum;
use App\Models\Order\Order;
use App\Models\TimeSlots;
use Illuminate\Http\JsonResponse;

class OrderService
{

    /**
     * OrderService constructor
     * @param OrderQueryService $orderQueryService
     */
    public function __construct(private OrderQueryService $orderQueryService)
    {
    }

    /**
     * Get order by method
     * @param array $criteria
     * @return Order|Order[]|JsonResponse
     */
    public function getOrderBy(array $criteria)
    {
        if (isset($criteria['session_id'])) {
            $order = $this->orderQueryService->findOrderBySession($criteria['session_id']);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order found for given criteria'
                ], 404);
            }
            return $order;
        }

        if (isset($criteria['order_id'])) {
            $order = $this->orderQueryService->findOrderById($criteria['order_id']);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No order found for given criteria'
                ], 404);
            }

            return $order;
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No criteria provided',
        ], 400);
    }

    /**
     * Confirm order method
     * @param Order $order
     * @return void
     */
    public function confirmOrder(Order $order)
    {
        $order->update([
            'status' => OrderStatusEnum::SOLD,
            'confirmed_at' => now(),
            'updated_at' => now(),
        ]);

        $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::SOLD);
    }

    /**
     * Cancel order method
     * @param Order $order
     * @return void
     */
    public function cancelOrder(Order $order)
    {
        $order->update([
            'status' => OrderStatusEnum::CANCELED,
            'cancelled_at' => now(),
            'cancel_reason' => 'Canceled by user',
        ]);

        $order->orderServices()->update([
            'is_canceled' => 1,
            'updated_at' => now(),
        ]);

        $this->changeOrderServicesTimeslotsState($order, TimeslotStateEnum::FREE);
    }

    /**
     * Method for changing service's timeslot state
     * @param Order $order
     * @param TimeslotStateEnum $status
     * @return void
     */
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
