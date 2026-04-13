<?php

namespace App\Models\Scopes\Workshop;

use App\Models\User;
use App\Models\WorkshopCategory;
use Illuminate\Database\Eloquent\Builder;

trait WorkshopSortScopes
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('starts_at');
    }

    /**
     * Upcoming workshops first (soonest start), then past (oldest start first).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeIndexOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN starts_at > ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('starts_at');
    }

    /**
     * Apply admin table sorting. Falls back to {@see scopeIndexOrder()}.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSortForAdminIndex(Builder $query, ?string $sort, ?string $direction): Builder
    {
        if ($sort === null || $sort === '') {
            return $query->indexOrder();
        }

        $dir = $direction === 'desc' ? 'desc' : 'asc';
        $startsAt = $this->qualifyColumn('starts_at');

        $query->reorder();

        return match ($sort) {
            'title' => $query->orderBy($this->qualifyColumn('title'), $dir),
            'starts_at' => $query->orderBy($startsAt, $dir),
            'category.name' => $query->orderBy(
                WorkshopCategory::query()
                    ->select('name')
                    ->whereColumn('workshop_categories.id', $this->qualifyColumn('workshop_category_id'))
                    ->limit(1),
                $dir,
            ),
            'creator.name' => $query->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', $this->qualifyColumn('created_by'))
                    ->limit(1),
                $dir,
            ),
            'timing_status' => $dir === 'asc'
                ? $query->indexOrder()
                : $query
                    ->orderByRaw("CASE WHEN {$startsAt} > ? THEN 1 ELSE 0 END", [now()])
                    ->orderBy($startsAt, 'desc'),
            default => $query->indexOrder(),
        };
    }
}
