<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedAppointmentResource extends JsonResource
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
            'summary' => $this->summary,
            'notes' => $this->notes,
            'recommendations' => $this->recommendations,
            'status' => $this->status,
            'doctor' => [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
                'surname' => $this->doctor->user->surname,
                'email' => $this->doctor->user->email,
            ],
            'patient' => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'surname' => $this->patient->surname,
                'phone' => $this->patient->phone,
                'email' => $this->patient->email,
            ],
            'service' => [
                'id' => $this->timeslot->service->id,
                'name' => $this->timeslot->service->name,
                'department' => $this->timeslot->service->department->content->title,
                'start_time' => $this->timeslot->start_time,
                'state' => $this->timeslot->state,
            ],
            'referral' => [
                'id' => $this->referral->id,
                'code' => $this->referral->referral_code,
                'expired_at' => $this->referral->expired_at,
                'data' => json_decode($this->referral->decoded_data),
            ],
            'hospital' => [
                'id' => $this->doctor->user->hospital_id,
                'title' => $this->doctor->user->hospital->content->title,
                'email' => $this->doctor->user->hospital->hospital_email,
                'phone' => $this->doctor->user->hospital->hospital_phone,
                'address' => $this->doctor->user->hospital->content->address,
            ]
        ];
    }
}
