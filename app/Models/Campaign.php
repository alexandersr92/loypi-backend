<?php

namespace App\Models;

use App\Traits\HasFileUploads;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory, HasUuids, HasFileUploads;

    /**
     * Boot del modelo - genera code automáticamente si no existe
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->code)) {
                $campaign->code = static::generateCode();
            }
        });
    }

    /**
     * Genera un code único de 4 caracteres
     */
    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(4));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    protected $fillable = [
        'business_id',
        'code',
        'type',
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
        'streak_time_limit_hours',
        'streak_reset_time',
        'per_customer_limit',
        'per_week_limit',
        'per_month_limit',
        'max_redemptions_per_day',
    ];

    protected function casts(): array
    {
        return [
            'limit' => 'integer',
            'redeemed_count' => 'integer',
            'reward_json' => 'array',
            'required_stamps' => 'integer',
            'active' => 'boolean',
            'streak_time_limit_hours' => 'integer',
            'per_customer_limit' => 'integer',
            'per_week_limit' => 'integer',
            'per_month_limit' => 'integer',
            'max_redemptions_per_day' => 'integer',
        ];
    }

    /**
     * Relación con el negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación many-to-many con los rewards a través de campaign_reward
     */
    public function rewards(): BelongsToMany
    {
        return $this->belongsToMany(Reward::class, 'campaign_reward')
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
     * Relación many-to-many con custom fields
     */
    public function customFields(): BelongsToMany
    {
        return $this->belongsToMany(CustomField::class, 'campaign_custom_field')
            ->using(CampaignCustomField::class)
            ->withPivot([
                'sort_order',
                'required_override',
            ])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Relación directa con campaign_custom_field (pivot)
     */
    public function campaignCustomFields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignCustomField::class);
    }

    /**
     * Relación con customer_campaigns
     */
    public function customerCampaigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerCampaign::class);
    }

    /**
     * Relación many-to-many con customers a través de customer_campaigns
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_campaigns')
            ->withPivot(['stamps', 'redeemed_at'])
            ->withTimestamps();
    }

    /**
     * Obtiene los campos que contienen archivos para este modelo
     */
    protected function getFileFields(): array
    {
        return ['cover_image', 'logo_url'];
    }
}
