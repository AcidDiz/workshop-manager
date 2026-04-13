<?php

namespace App\Support\Filters\Workshops;

use App\Enums\Workshop\WorkshopStatusEnum;
use App\Http\Resources\WorkshopCategory\WorkshopCategoryFilterSelectOptionResource;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use Illuminate\Support\Collection;

/**
 * Workshops index for **non-admin** viewers (e.g. employees with `workshops.view` but not `workshops.manage`): card layout, card filter bar metadata.
 */
class WorkshopUserFilters
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *   workshops: Collection<int, Workshop>,
     *   filters: array<string, mixed>,
     *   cardFilterFields: list<array<string, mixed>>
     * }
     */
    public function index(array $validated): array
    {
        $requestedStatus = $this->normalizeStatus($validated['status'] ?? null);
        $effectiveStatus = $requestedStatus ?? WorkshopStatusEnum::Upcoming->value;

        $workshops = Workshop::query()
            ->withIndexRelations()
            ->withConfirmedRegistrationCount()
            ->status($effectiveStatus)
            ->filterCategoryId($validated['category_id'] ?? null)
            ->searchTitle($validated['title'] ?? null)
            ->startsOn($validated['starts_on'] ?? null)
            ->indexOrder()
            ->get();

        $categories = WorkshopCategory::query()->orderBy('name')->get();

        $filters = [
            'status' => $requestedStatus,
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'starts_on' => $validated['starts_on'] ?? null,
            'created_by' => null,
            'sort' => null,
            'direction' => null,
        ];

        return [
            'workshops' => $workshops,
            'filters' => $filters,
            'cardFilterFields' => $this->cardFilterFields($categories),
        ];
    }

    /**
     * @param  Collection<int, WorkshopCategory>  $categories
     * @return list<array<string, mixed>>
     */
    private function cardFilterFields(Collection $categories): array
    {
        $categoryOptions = WorkshopCategoryFilterSelectOptionResource::collection($categories)->resolve();
        $statusOptions = WorkshopStatusEnum::filterSelectOptions();

        return [
            [
                'param' => 'category_id',
                'label' => 'Category',
                'placeholder' => 'Select Category',
                'input_type' => 'select',
                'options' => $categoryOptions,
            ],
            [
                'param' => 'starts_on',
                'label' => 'Starts on',
                'placeholder' => 'Date',
                'input_type' => 'date',
            ],
            [
                'param' => 'status',
                'label' => 'Timing',
                'placeholder' => 'Select Status',
                'input_type' => 'select',
                'options' => $statusOptions,
            ],
        ];
    }

    private function normalizeStatus(mixed $requested): ?string
    {
        if ($requested instanceof WorkshopStatusEnum) {
            return $requested->value;
        }

        if ($requested === null || $requested === '') {
            return null;
        }

        return (string) $requested;
    }
}
