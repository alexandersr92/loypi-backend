<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
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
            'business_id' => $this->business_id,
            'code' => $this->code,
            'name' => $this->name,
            'active' => $this->active,
            'failed_login_attempts' => $this->failed_login_attempts,
            'locked_until' => $this->locked_until?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'business' => $this->whenLoaded('business', function () {
                return [
                    'id' => $this->business->id,
                    'slug' => $this->business->slug,
                    'name' => $this->business->name,
                ];
            }),
        ];
    }
}
