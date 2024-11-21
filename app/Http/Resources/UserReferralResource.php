<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserReferralResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'referral_id' => $this->id,
            'referral_code' => $this->referral_code,
            'decoded_data' => json_decode(base64_decode($this->encoded_data), true),
            'expired_at' => $this->expired_at
        ];
    }
}
