<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCampaign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'customer_id',
        'campaign_id',
        'stamps',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'stamps' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * Relación con el customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación con la campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relación con stamps
     */
    public function stamps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Stamp::class);
    }

    /**
     * Relación con reward_unlocks
     */
    public function rewardUnlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RewardUnlock::class);
    }

    /**
     * Relación con redemptions
     */
    public function redemptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Redemption::class);
    }
}
