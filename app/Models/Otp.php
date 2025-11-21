<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'phone',
        'code',
        'type',
        'status',
        'expires_at',
        'verified_at',
        'ip_address',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * Verifica si el OTP está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verifica si el OTP ya fue verificado
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Marca el OTP como verificado
     */
    public function markAsVerified(): void
    {
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }

    /**
     * Marca el OTP como expirado
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }
}
