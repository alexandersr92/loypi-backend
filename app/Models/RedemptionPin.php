<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedemptionPin extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'redemption_id',
        'pin',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Relación con redemption
     */
    public function redemption(): BelongsTo
    {
        return $this->belongsTo(Redemption::class);
    }

    /**
     * Verificar si el PIN está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verificar si el PIN es válido
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && $this->verified_at === null;
    }
}
