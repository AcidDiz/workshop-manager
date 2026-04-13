<?php

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Value/label pair for select inputs (filters, forms) backed by {@see User}.
 *
 * @mixin User
 */
class FilterSelectOptionResource extends JsonResource
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
