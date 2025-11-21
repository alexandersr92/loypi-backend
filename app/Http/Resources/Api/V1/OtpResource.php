<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtpResource extends JsonResource
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
            'phone' => $this->phone,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,
            'expires_at' => $this->expires_at->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'ip_address' => $this->ip_address,
            'is_expired' => $this->isExpired(),
            'is_verified' => $this->isVerified(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
