<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Business extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'logo',
        'branding_json',
        'address',
        'phone',
        'email',
        'website',
        'city',
        'state',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'branding_json' => 'array',
        ];
    }

    /**
     * Relación con el usuario propietario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el staff
     */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * Relación con las campaigns
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Relación con los custom fields
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    /**
     * Relación con los customers
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Boot del modelo - genera slug automáticamente si no existe
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = Str::slug($business->name);
                
                // Asegurar que el slug sea único
                $originalSlug = $business->slug;
                $counter = 1;
                while (static::where('slug', $business->slug)->exists()) {
                    $business->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

}
