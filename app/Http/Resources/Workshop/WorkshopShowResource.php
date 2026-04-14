<?php

namespace App\Http\Resources\Workshop;

use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workshop
 */
class WorkshopShowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Workshop $workshop */
        $workshop = $this->resource;

        $confirmed = (int) ($workshop->confirmed_registrations_count ?? 0);
        $waiting = (int) ($workshop->waiting_list_registrations_count ?? 0);

        return [
            'id' => $workshop->id,
            'title' => $workshop->title,
            'description' => $workshop->description,
            'starts_at' => $workshop->starts_at?->toIso8601String(),
            'ends_at' => $workshop->ends_at?->toIso8601String(),
            'capacity' => $workshop->capacity,
            'confirmed_registrations_count' => $confirmed,
            'waiting_list_registrations_count' => $waiting,
            'enrollment' => $confirmed.'/'.$workshop->capacity,
            'category' => $workshop->relationLoaded('category') && $workshop->category
                ? [
                    'id' => $workshop->category->id,
                    'name' => $workshop->category->name,
                ]
                : [
                    'id' => null,
                    'name' => '—',
                ],
            'creator' => $workshop->relationLoaded('creator') && $workshop->creator
                ? [
                    'id' => $workshop->creator->id,
                    'name' => $workshop->creator->name,
                ]
                : [
                    'id' => null,
                    'name' => '—',
                ],
        ];
    }
}
