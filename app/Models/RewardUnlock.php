<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardUnlock extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reward_id',
        'customer_campaign_id',
        'unlocked_at',
        'expires_at',
        'redeemed_at',
        'redemption_id',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function customerCampaign(): BelongsTo
    {
        return $this->belongsTo(CustomerCampaign::class);
    }

    public function redemption(): BelongsTo
    {
        return $this->belongsTo(Redemption::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }
}

