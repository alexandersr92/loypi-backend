<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Model implements Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens;
    use \Illuminate\Auth\Authenticatable;

    protected $fillable = [
        'business_id',
        'short_code',
        'phone',
        'name',
    ];

    /**
     * Boot del modelo - genera short_code automáticamente si no existe
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->short_code)) {
                $customer->short_code = static::generateShortCode();
            }
        });
    }

    /**
     * Genera un short_code único de 6 caracteres
     */
    protected static function generateShortCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Relación con el negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación con los tokens de Sanctum (PersonalAccessToken)
     */
    public function tokens(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Laravel\Sanctum\PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Relación con los customer tokens
     */
    public function customerTokens(): HasMany
    {
        return $this->hasMany(CustomerToken::class);
    }

    /**
     * Relación con los valores de custom fields
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(CustomerFieldValue::class);
    }

    /**
     * Relación con customer_campaigns
     */
    public function customerCampaigns(): HasMany
    {
        return $this->hasMany(CustomerCampaign::class);
    }

    /**
     * Relación many-to-many con campaigns a través de customer_campaigns
     */
    public function campaigns(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'customer_campaigns')
            ->using(CustomerCampaign::class)
            ->withPivot(['stamps', 'redeemed_at'])
            ->withTimestamps();
    }

    /**
     * Obtener el token activo más reciente
     */
    public function getActiveToken()
    {
        return $this->customerTokens()
            ->where('active', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
