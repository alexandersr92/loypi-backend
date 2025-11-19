<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BusinessResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'logo' => $business->logo,
                    'user_id' => $business->user_id,
                    'country' => $business->country,
                ];
            }),
        ];
    }
}
