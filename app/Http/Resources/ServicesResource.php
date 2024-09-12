<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicesResource extends JsonResource
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
            "name" => $this->name,
            "description" => $this->description,
            "doctors" => $this->doctors
                ->filter(function ($doctor) {
                    return $doctor->hidden === 0; })
                ->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->user->name,
                        'surname' => $doctor->user->surname,
                    ];
                }),
            'hospitals' => $this->hospitals->map(function ($hospital) {
                return [
                    'id' => $hospital->id,
                    'title' => $hospital->content->title,
                ];
            }),
            'department' => [
                'id' => $this->department->id,
                'title' => $this->department->content->title,
            ]
        ];
    }
}
