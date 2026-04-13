<?php

namespace App\Models;

use App\Models\Scopes\Workshop\WorkshopFilterScopes;
use App\Models\Scopes\Workshop\WorkshopSortScopes;
use Database\Factories\WorkshopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'workshop_category_id', 'starts_at', 'ends_at', 'capacity', 'created_by'])]
class Workshop extends Model
{
    /** @use HasFactory<WorkshopFactory> */
    use HasFactory;

    use WorkshopFilterScopes;
    use WorkshopSortScopes;

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
