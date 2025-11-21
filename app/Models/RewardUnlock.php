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
        'customer_campaign_id',
        'reward_id',
        'campaign_reward_id',
        'unlocked_at',
        'expires_at',
        'redeemed_at',
        'redemption_id',
        'status',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

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
     * Relación con campaign_reward
     */
    public function campaignReward(): BelongsTo
    {
        return $this->belongsTo(CampaignReward::class);
    }

    /**
     * Relación con redemption
     */
    public function redemption(): BelongsTo
    {
        return $this->belongsTo(Redemption::class);
    }

    /**
     * Verificar si el unlock está expirado
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Verificar si el unlock está disponible para canjear
     */
    public function isAvailable(): bool
    {
        return $this->status === 'unlocked' && !$this->isExpired();
    }
}
