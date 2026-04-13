<?php

namespace App\Http\Requests\Workshops;

use App\Enums\Workshop\WorkshopStatusEnum;
use App\Models\Workshop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListWorkshopsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Workshop::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'status' => ['sometimes', 'nullable', Rule::enum(WorkshopStatusEnum::class)],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:workshop_categories,id'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'starts_on' => ['sometimes', 'nullable', 'date'],
            'created_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'sort' => ['sometimes', 'nullable', Rule::in([
                'title',
                'category.name',
                'starts_at',
                'creator.name',
                'timing_status',
            ])],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
        ];

        if (! $this->user()?->can('workshops.manage')) {
            unset($rules['created_by']);
            unset($rules['sort']);
            unset($rules['direction']);
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status') && $this->query('status') === '') {
            $this->merge(['status' => null]);
        }

        if ($this->has('sort') && $this->query('sort') === '') {
            $this->merge(['sort' => null]);
        }

        if ($this->has('direction') && $this->query('direction') === '') {
            $this->merge(['direction' => null]);
        }
    }
}
