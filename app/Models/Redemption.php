<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Redemption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_campaign_id',
        'reward_id',
        'staff_id',
        'confirmed_at',
        'meta',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function customerCampaign(): BelongsTo
    {
        return $this->belongsTo(CustomerCampaign::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function redemptionPin(): HasOne
    {
        return $this->hasOne(RedemptionPin::class);
    }

    public function rewardUnlocks(): HasOne
    {
        return $this->hasOne(RewardUnlock::class);
    }
}

