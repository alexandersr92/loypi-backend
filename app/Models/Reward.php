<?php

namespace App\Models;

use App\Traits\HasFileUploads;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use HasFactory, HasUuids, HasFileUploads, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'type',
        'description',
        'image_url',
        'reward_json',
    ];

    protected function casts(): array
    {
        return [
            'reward_json' => 'array',
        ];
    }

    /**
     * Relación many-to-many con las campaigns a través de campaign_reward
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_reward')
            ->using(CampaignReward::class)
            ->withPivot([
                'threshold_int',
                'per_customer_limit',
                'global_limit',
                'redeemed_count',
                'active',
                'sort_order',
                'expires_after_days',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Relación directa con campaign_reward (pivot)
     */
    public function campaignRewards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignReward::class);
    }

    /**
     * Relación con el business (para templates)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación con reward_unlocks
     */
    public function rewardUnlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RewardUnlock::class);
    }

    /**
     * Obtiene los campos que contienen archivos para este modelo
     */
    protected function getFileFields(): array
    {
        return ['image_url'];
    }
}
