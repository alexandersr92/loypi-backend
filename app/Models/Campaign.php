<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'business_id',
        'name',
        'description',
        'limit',
        'redeemed_count',
        'reward_json',
        'required_stamps',
        'active',
        'cover_image',
        'cover_color',
        'logo_url',
    ];

    protected $casts = [
        'reward_json' => 'array',
        'active' => 'boolean',
        'limit' => 'integer',
        'redeemed_count' => 'integer',
        'required_stamps' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function customerCampaigns(): HasMany
    {
        return $this->hasMany(CustomerCampaign::class);
    }

    public function customerStreaks(): HasMany
    {
        return $this->hasMany(CustomerStreak::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }
}

