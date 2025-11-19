<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Staff extends Model implements Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens;
    
    use \Illuminate\Auth\Authenticatable;

    protected $fillable = [
        'business_id',
        'code',
        'name',
        'passcode_hash',
        'active',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Relación con el negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Verifica si el PIN es correcto
     */
    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->passcode_hash);
    }

    /**
     * Verifica si el staff está bloqueado
     */
    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }

        return $this->locked_until->isFuture();
    }

    /**
     * Incrementa los intentos fallidos de login
     */
    public function incrementFailedAttempts(int $maxAttempts = 5, int $lockMinutes = 30): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= $maxAttempts) {
            $this->locked_until = now()->addMinutes($lockMinutes);
            $this->save();
        }
    }

    /**
     * Resetea los intentos fallidos (después de login exitoso)
     */
    public function resetFailedAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Desbloquea el staff (solo owner puede hacer esto)
     */
    public function unlock(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }
}
