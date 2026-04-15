<?php

namespace App\Models\Support\Scopes\Workshop;

use App\Enums\Workshop\WorkshopStatusEnum;
use Illuminate\Database\Eloquent\Builder;

trait WorkshopFilterScopes
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with(['category', 'creator']);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return match (WorkshopStatusEnum::tryFrom($status)) {
            WorkshopStatusEnum::Upcoming => $query->upcoming(),
            WorkshopStatusEnum::Closed => $query->closed(),
            default => $query,
        };
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFilterCategoryId(Builder $query, ?int $categoryId): Builder
    {
        return $query->when(
            $categoryId !== null,
            fn (Builder $query): Builder => $query->where('workshop_category_id', $categoryId),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearchTitle(Builder $query, ?string $title): Builder
    {
        $title = $title !== null ? trim($title) : null;

        return $query->when(
            $title !== null && $title !== '',
            fn (Builder $query): Builder => $query->where('title', 'like', '%'.$title.'%'),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStartsOn(Builder $query, ?string $startsOn): Builder
    {
        return $query->when(
            $startsOn !== null,
            fn (Builder $query): Builder => $query->whereDate('starts_at', $startsOn),
        );
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCreatedBy(Builder $query, ?int $createdBy): Builder
    {
        return $query->when(
            $createdBy !== null,
            fn (Builder $query): Builder => $query->where('created_by', $createdBy),
        );
    }
}
