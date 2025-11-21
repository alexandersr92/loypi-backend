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
        'reward_unlock_id',
        'customer_campaign_id',
        'reward_id',
        'staff_id',
        'pin_code',
        'confirmed_at',
        'meta',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Relación con reward_unlock
     */
    public function rewardUnlock(): BelongsTo
    {
        return $this->belongsTo(RewardUnlock::class);
    }

    /**
     * Relación con customer_campaign
     */
    public function customerCampaign(): BelongsTo
    {
        return $this->belongsTo(CustomerCampaign::class);
    }

    /**
     * Relación con reward
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * Relación con staff
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Relación con redemption_pin
     */
    public function redemptionPin(): HasOne
    {
        return $this->hasOne(RedemptionPin::class);
    }
}
