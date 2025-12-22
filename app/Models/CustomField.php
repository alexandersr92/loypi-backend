<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomField extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_id',
        'key',
        'label',
        'description',
        'type',
        'required',
        'extra',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'extra' => 'array',
            'active' => 'boolean',
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
     * Relación con las opciones (para campos tipo select)
     */
    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class)->orderBy('sort_order');
    }

    /**
     * Relación many-to-many con campaigns
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_custom_field')
            ->using(CampaignCustomField::class)
            ->withPivot([
                'sort_order',
                'required_override',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Relación con las validaciones
     */
    public function validations(): HasMany
    {
        return $this->hasMany(CustomFieldValidation::class);
    }

    /**
     * Relación con los valores de los customers
     */
    public function customerValues(): HasMany
    {
        return $this->hasMany(CustomerFieldValue::class);
    }

    /**
     * Verificar si el field tiene valores de customers
     */
    public function hasCustomerValues(): bool
    {
        return $this->customerValues()->exists();
    }
}
