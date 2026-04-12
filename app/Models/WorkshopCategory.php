<?php

namespace App\Models;

use Database\Factories\WorkshopCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class WorkshopCategory extends Model
{
    /** @use HasFactory<WorkshopCategoryFactory> */
    use HasFactory;

    /** @return HasMany<Workshop, $this> */
    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class, 'workshop_category_id');
    }
}
