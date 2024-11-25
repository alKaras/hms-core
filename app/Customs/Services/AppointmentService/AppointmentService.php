<?php

namespace App\Customs\Services\AppointmentService;

use App\Customs\Services\MeetHandlerService;
use App\Enums\AppointmentsStatusEnum;
use App\Models\MedAppointments;
use App\Models\MedCard;
use App\Models\Order\Order;
use App\Models\User\User;

class AppointmentService
{

    public function __construct(private MeetHandlerService $meetHandlerService)
    {
    }

    public function makeAppointmentOnConfirmation(Order $order): void
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
                'meet_link' => $slot->online ? $this->meetHandlerService->createLink() : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $appointment->save();
        }
    }
}
