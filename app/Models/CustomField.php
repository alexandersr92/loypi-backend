<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'campaign_id',
        'key',
        'label',
        'type',
        'required',
        'extra',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
        'extra' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(CustomFieldOption::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(CustomFieldValidation::class);
    }

    public function customerFieldValues(): HasMany
    {
        return $this->hasMany(CustomerFieldValue::class);
    }
}

