<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'name',
        'type',
        'threshold_int',
        'description',
        'per_customer_limit',
        'global_limit',
        'redeemed_count',
        'active',
        'reward_json',
    ];

    protected $casts = [
        'type' => 'string',
        'threshold_int' => 'integer',
        'per_customer_limit' => 'integer',
        'global_limit' => 'integer',
        'redeemed_count' => 'integer',
        'active' => 'boolean',
        'reward_json' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function rewardUnlocks(): HasMany
    {
        return $this->hasMany(RewardUnlock::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }
}

