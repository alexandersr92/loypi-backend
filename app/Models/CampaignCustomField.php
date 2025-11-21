<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CampaignCustomField extends Pivot
{
    use HasUuids;

    protected $table = 'campaign_custom_field';

    protected $fillable = [
        'campaign_id',
        'custom_field_id',
        'sort_order',
        'required_override',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'required_override' => 'boolean',
        ];
    }
}
