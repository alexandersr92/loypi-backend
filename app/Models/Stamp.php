<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stamp extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_campaign_id',
        'staff_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function customerCampaign(): BelongsTo
    {
        return $this->belongsTo(CustomerCampaign::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}

