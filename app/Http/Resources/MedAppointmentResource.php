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
            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name ?? '',
                'surname' => $this->doctor->user->surname ?? '',
                'email' => $this->doctor->user->email ?? '',
            ] : null,
            'patient' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'surname' => $this->user->surname,
                'phone' => $this->user->phone,
                'email' => $this->user->email,
            ] : null,
            'service' => $this->timeslot && $this->timeslot->service ? [
                'id' => $this->timeslot->service->id,
                'name' => $this->timeslot->service->name,
                'department' => $this->timeslot->service->department->content->title ?? '',
                'start_time' => $this->timeslot->start_time,
                'state' => $this->timeslot->state,
            ] : null,
            'referral' => $this->referral ? [
                'id' => $this->referral->id,
                'code' => $this->referral->referral_code,
                'expired_at' => $this->referral->expired_at,
                'data' => json_decode($this->referral->decoded_data),
            ] : null,
            'hospital' => $this->doctor && $this->doctor->user && $this->doctor->user->hospital ? [
                'id' => $this->doctor->user->hospital_id,
                'title' => $this->doctor->user->hospital->content->title ?? '',
                'email' => $this->doctor->user->hospital->hospital_email ?? '',
                'phone' => $this->doctor->user->hospital->hospital_phone ?? '',
                'address' => $this->doctor->user->hospital->content->address ?? '',
            ] : null,
        ];
    }
}
