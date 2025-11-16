<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, HasUuids, HasApiTokens, Notifiable;

    protected $fillable = [
        'short_code',
        'phone',
        'name',
    ];

    public function customerTokens(): HasMany
    {
        return $this->hasMany(CustomerToken::class);
    }

    public function customerCampaigns(): HasMany
    {
        return $this->hasMany(CustomerCampaign::class);
    }

    public function customerStreaks(): HasMany
    {
        return $this->hasMany(CustomerStreak::class);
    }

    public function customerFieldValues(): HasMany
    {
        return $this->hasMany(CustomerFieldValue::class);
    }
}

