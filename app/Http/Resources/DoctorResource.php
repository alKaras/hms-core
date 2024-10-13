<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "name" => $this->user->name,
            'surname' => $this->user->surname,
            'email' => $this->user->email,
            'specialization' => $this->specialization,
            'services' => $this->services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name
                ];
            }),
            'departments' => $this->departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->content->title,
                ];
            }),
            'hospitalId' => $this->hospital_id,
        ];
    }
}
