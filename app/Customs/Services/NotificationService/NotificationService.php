<?php

namespace App\Customs\Services\NotificationService;

use App\Models\MedAppointments;
use App\Models\Order\Order;
use App\Models\User\User;
use App\Notifications\AppointmentSummaryNotification;
use App\Notifications\DoctorCredentialsNotification;
use App\Notifications\RegisteredUserCredentials;
use App\Notifications\TimeSlotConfirmationNotification;
use App\Notifications\UserReferralNotification;

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

    public function sendAppointmentSummary(MedAppointments $appointment)
    {
        $user = User::find($appointment->user_id);
        $user->notify(new AppointmentSummaryNotification($appointment));
    }

    public function sendCredentials(User $user, $password, $isDoctor = false)
    {
        if ($isDoctor){
            $user->notify(new DoctorCredentialsNotification($user->email, $password));
        } else {
            $user->notify(new RegisteredUserCredentials($user->email, $password));
        }

    }

    public function sendReferral(User $user, $code)
    {
        $user->notify(new UserReferralNotification($code));
    }


}
