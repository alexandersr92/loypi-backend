<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CampaignReward extends Pivot
{
    use HasUuids;

    protected $table = 'campaign_reward';
    
    public $incrementing = false;

    protected $fillable = [
        'campaign_id',
        'reward_id',
        'threshold_int',
        'per_customer_limit',
        'global_limit',
        'redeemed_count',
        'active',
        'sort_order',
        'expires_after_days',
    ];

    protected function casts(): array
    {
        return [
            'threshold_int' => 'integer',
            'per_customer_limit' => 'integer',
            'global_limit' => 'integer',
            'redeemed_count' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
            'expires_after_days' => 'integer',
        ];
    }

    /**
     * Relación con la campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Relación con el reward
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }
}
