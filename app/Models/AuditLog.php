<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'action',
        'actor_type',
        'actor_id',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta' => 'array',
    ];

    /**
     * Relación polimórfica con el actor (User, Staff, Customer)
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor');
    }

    /**
     * Relación polimórfica con el modelo auditado (Campaign, Customer, etc.)
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable');
    }

    /**
     * Helper para crear un log de auditoría
     */
    public static function log(
        string $action,
        Model $actor,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null
    ): self {
        $request = request();

        return self::create([
            'action' => $action,
            'actor_type' => get_class($actor),
            'actor_id' => $actor->id,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'meta' => $meta,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
