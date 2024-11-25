<?php

namespace App\Customs\Services\NotificationService;

use App\Models\Order\Order;
use App\Models\User\User;
use App\Notifications\TimeSlotConfirmationNotification;

class NotificationService
{
    public function sendOrderConfirmation(Order $order)
    {
        $user = User::find($order->user->id);
        $timeSlots = $order->orderServices->map(function ($orderService) {
            return $orderService->timeSlot; // Map each service to its associated time slot
        });

        $user->notify(new TimeSlotConfirmationNotification($timeSlots));
    }
}
