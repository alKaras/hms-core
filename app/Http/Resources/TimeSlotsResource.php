<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'department' => $this->service->department->content->title,
            ],
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'surname' => $this->doctor->user->surname,
                'email' => $this->doctor->user->email,
            ],
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'price' => $this->price,
        ];
    }
}
