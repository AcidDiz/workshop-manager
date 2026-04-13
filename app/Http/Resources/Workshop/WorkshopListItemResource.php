<?php

namespace App\Http\Resources\Workshop;

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Enums\Workshop\WorkshopStatusEnum;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Workshop
 */
class WorkshopListItemResource extends JsonResource
{
    public function __construct(
        $resource,
        private ?WorkshopRegistrationStatusEnum $myRegistrationStatus = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Workshop $workshop */
        $workshop = $this->resource;

        $isFuture = $workshop->starts_at->isFuture();
        $timingEnum = $isFuture ? WorkshopStatusEnum::Upcoming : WorkshopStatusEnum::Closed;
        $confirmed = (int) ($workshop->confirmed_registrations_count ?? 0);

        return [
            'id' => $workshop->id,
            'title' => $workshop->title,
            'description' => $workshop->description,
            'starts_at' => $workshop->starts_at->toIso8601String(),
            'ends_at' => $workshop->ends_at->toIso8601String(),
            'capacity' => $workshop->capacity,
            'confirmed_registrations_count' => $confirmed,
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
            'timing_status' => $isFuture ? 'upcoming' : 'closed',
            'timing_status_badge_class' => $timingEnum->badgeClassName(),
            'my_registration_status' => $this->myRegistrationStatus?->value,
        ];
    }
}
