<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rolesByPriorityDesc = $this->roles()->orderBy('priority', 'desc')->get()->pluck('title');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'active' => (int) $this->active,
            'email_verified' => $this->email_verified_at ? 'verified' : null,
            'roles' => $rolesByPriorityDesc->toArray()
        ];
    }
}