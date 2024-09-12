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
            "doctors" => [
                'id' => $this->doctors->id,
                'name' => $this->doctors->name,
            ],
            'hospitals' => [
                'id' => $this->hospitals->id,
                'title' => $this->hospitals->pluck('content.title'),
            ],
            'department' => [
                'id' => $this->department->id,
                'title' => $this->department->pluck('content.title'),
            ]
        ];
    }
}
