<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedCardResource extends JsonResource
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
            'firstname' => $this->firstname ?? $this->user->name,
            'lastname' => $this->lastname ?? $this->user->surname,
            'date_birthday' => $this->date_birthday,
            'gender' => $this->gender,
            'contact_number' => $this->contact_number ?? $this->user->phone,
            'address' => $this->address,
            'blood_type' => $this->blood_type,
            'allergies' => $this->allergies,
            'chronic_conditions' => $this->chronic_conditions,
            'current_medications' => $this->current_medications,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'insurrance_details' => $this->insurrance_details,
            'additional_notes' => $this->additional_notes,
            'status' => $this->status,
            'appointments' => $this->appointments !== null ? $this->appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'doctor' => $appointment->doctor ? [
                        'id' => $appointment->doctor->id,
                        'name' => $appointment->doctor->user->name ?? '',
                        'surname' => $appointment->doctor->user->surname ?? '',
                        'email' => $appointment->doctor->user->email ?? '',
                    ] : null,
                    'service' => $appointment->timeslot && $appointment->timeslot->service ? [
                        'id' => $appointment->timeslot->service->id,
                        'name' => $appointment->timeslot->service->name,
                        'department' => $appointment->timeslot->service->department->content->title ?? '',
                        'start_time' => $appointment->timeslot->start_time,
                        'state' => $appointment->timeslot->state,
                    ] : null,
                    'summary' => $appointment->summary,
                    'notes' => $appointment->notes,
                    'recommendations' => $appointment->recommendations,
                    'hospital' => $appointment->doctor && $appointment->doctor->user && $appointment->doctor->user->hospital ? [
                        'id' => $appointment->doctor->user->hospital_id,
                        'title' => $appointment->doctor->user->hospital->content->title ?? '',
                        'email' => $appointment->doctor->user->hospital->hospital_email ?? '',
                        'phone' => $appointment->doctor->user->hospital->hospital_phone ?? '',
                        'address' => $appointment->doctor->user->hospital->content->address ?? '',
                    ] : null,
                ];
            }) : [],
        ];
    }
}
