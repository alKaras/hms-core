<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class HospitalReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'body' => $this->body,
            'user' => [
                'id' => $this->user->id,
                'firstname' => $this->user->name,
                'surname' => $this->user->surname
            ],
            'hospital' => [
                'id' => $this->hospital->id,
                'title' => $this->hospital->content->title,
            ],
            'created_at' => ($this->created_at)->toDateTimeString(),
        ];
    }
}
