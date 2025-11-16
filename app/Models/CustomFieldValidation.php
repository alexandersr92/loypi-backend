<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldValidation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'custom_field_id',
        'operator',
        'value_string',
        'value_number',
        'value_date',
        'message',
    ];

    protected $casts = [
        'value_number' => 'decimal:2',
        'value_date' => 'date',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}

