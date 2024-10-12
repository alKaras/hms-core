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
            "timesot" => [
                "id" => $this->timeSlot->id,
                // "doctor" => [
                //     "id" => $this->timeSlot->doctor->id,
                //     "fullname" => $this->timeSlot->doctor->user->name . " " . $this->timeSlot->doctor->user->surname,
                //     "email" => $this->timeSlot->doctor->user->email,
                // ],
                "service" => [
                    "id" => $this->timeSLot->service->id,
                    "name" => $this->timeSlot->service->name,
                    "department" => $this->timeSlot->service->department->content->title,
                ],
                "department" => [
                    "id" => $this->timeSlot->service->department->id,
                    "title" => $this->timeSlot->service->department->content->title,
                ],
                "start_time" => $this->timeSlot->start_time,
                "end_time" => $this->timeSlot->end_time,
                "price" => $this->timeSlot->price,
            ]
        ];
    }
}
