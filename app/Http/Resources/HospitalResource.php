<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HospitalResource extends JsonResource
{
    public static $wrap = false;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "alias" => $this->alias,
            "hospital_email" => $this->hospital_email ?? '',
            "hospital_phone" => $this->hospital_phone ?? '',
            "content" => $this->whenLoaded('content', function () {
                return [
                    "title" => $this->content->title ?? '',
                    "description" => $this->content->description ?? '',
                    "address" => $this->content->address ?? '',
                ];
            }, [])
        ];
    }
}
