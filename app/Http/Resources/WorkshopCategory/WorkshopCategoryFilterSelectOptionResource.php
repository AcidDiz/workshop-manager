<?php

namespace App\Http\Resources\WorkshopCategory;

use App\Models\WorkshopCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Value/label pair for select inputs (filters, forms) backed by {@see WorkshopCategory}.
 *
 * @mixin WorkshopCategory
 */
class WorkshopCategoryFilterSelectOptionResource extends JsonResource
{
    /**
     * @return array{value: string, label: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => (string) $this->id,
            'label' => $this->name,
        ];
    }
}
