<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFieldValue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'campaign_id',
        'customer_id',
        'custom_field_id',
        'string_value',
        'number_value',
        'date_value',
        'boolean_value',
    ];

    protected function casts(): array
    {
        return [
            'number_value' => 'decimal:2',
            'date_value' => 'date',
            'boolean_value' => 'boolean',
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
     * Relación con la campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relación con el custom field
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * Relación con el customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
