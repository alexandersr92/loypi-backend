<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RewardUnlockResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($unlock) {
                return new RewardUnlockResource($unlock);
            }),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
