<?php

namespace App\Models;

use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'workshop_category_id', 'starts_at', 'ends_at', 'capacity', 'created_by'])]
class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now());
    }

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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<WorkshopCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkshopCategory::class, 'workshop_category_id');
    }

    /** @return HasMany<WorkshopRegistration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(WorkshopRegistration::class);
    }
}
