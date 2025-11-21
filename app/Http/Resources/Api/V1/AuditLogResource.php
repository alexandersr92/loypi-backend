<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
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
            'action' => $this->action,
            'actor_type' => $this->actor_type,
            'actor_id' => $this->actor_id,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'description' => $this->description,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'meta' => $this->meta,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Relaciones
            'actor' => $this->whenLoaded('actor', function () {
                return [
                    'id' => $this->actor->id,
                    'name' => $this->actor->name ?? $this->actor->short_code ?? null,
                    'type' => class_basename($this->actor_type),
                ];
            }),
            
            'auditable' => $this->whenLoaded('auditable', function () {
                return [
                    'id' => $this->auditable->id,
                    'name' => $this->auditable->name ?? $this->auditable->title ?? null,
                    'type' => class_basename($this->auditable_type),
                ];
            }),
        ];
    }
}
