<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "is_canceled" => (int) $this->is_canceled,
            "order" => [
                "id" => $this->order->id,
                "total_amount" => $this->order->total_amount,
                "created_at" => $this->order->created_at,
                "confirmed_at" => $this->order->confirmed_at,
                "status" => $this->order->status,
                "reserve_exp" => $this->order->reserve_exp,
                "cancelled_at" => $this->order->cancelled_at,
                "cancel_reason" => $this->order->cancel_reason,
            ],
            "timesot" => [
                "id" => $this->timeSlot->id,
                "doctor" => [
                    "id" => $this->timeSlot->doctor->id,
                    "fullname" => $this->timeSlot->doctor->user->name . " " . $this->timeSlot->doctor->user->surname,
                    "email" => $this->timeSlot->doctor->user->email,
                ],
                "service" => [
                    "id" => $this->timeSLot->service->id,
                    "name" => $this->timeSlot->service->name,
                    "department" => $this->timeSlot->service->department->content->title,
                ],
                "start_time" => $this->start_time,
                "end_time" => $this->end_time,
                "price" => $this->price,
            ]
        ];
    }
}
