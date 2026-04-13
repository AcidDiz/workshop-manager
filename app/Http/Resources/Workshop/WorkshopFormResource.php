<?php

namespace App\Http\Resources\Workshop;

use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workshop
 */
class WorkshopFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Workshop $workshop */
        $workshop = $this->resource;

        $tz = config('app.timezone');

        return [
            'id' => $workshop->id,
            'title' => $workshop->title,
            'description' => $workshop->description,
            'workshop_category_id' => $workshop->workshop_category_id,
            'starts_at' => $workshop->starts_at->copy()->timezone($tz)->format('Y-m-d\TH:i'),
            'ends_at' => $workshop->ends_at->copy()->timezone($tz)->format('Y-m-d\TH:i'),
            'capacity' => $workshop->capacity,
        ];
    }
}
