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
        'customer_id',
        'custom_field_id',
        'string_value',
        'number_value',
        'date_value',
        'boolean_value',
    ];

    protected $casts = [
        'number_value' => 'decimal:2',
        'date_value' => 'date',
        'boolean_value' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}

