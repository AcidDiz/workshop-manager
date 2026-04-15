<?php

namespace App\Models\Support\Filters\Workshops;

use App\Enums\Workshop\WorkshopStatusEnum;
use App\Models\Support\Tables\Workshop\WorkshopTableColumns;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use Illuminate\Support\Collection;

/**
 * Workshops index for **admin** viewers (`workshops.manage`): table layout, sort, creator filter, column metadata.
 */
class WorkshopAdminFilters
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *   workshops: Collection<int, Workshop>,
     *   filters: array<string, mixed>,
     *   workshopTableColumns: list<array<string, mixed>>
     * }
     */
    public function index(array $validated): array
    {
        $requestedStatus = $this->normalizeStatus($validated['status'] ?? null);
        $effectiveStatus = $requestedStatus ?? WorkshopStatusEnum::All->value;

        $requestedSort = $validated['sort'] ?? null;
        $requestedDirection = $validated['direction'] ?? null;
        $direction = $requestedDirection === 'desc' ? 'desc' : 'asc';

        $workshops = Workshop::query()
            ->withIndexRelations()
            ->withConfirmedRegistrationCount()
            ->status($effectiveStatus)
            ->filterCategoryId($validated['category_id'] ?? null)
            ->searchTitle($validated['title'] ?? null)
            ->startsOn($validated['starts_on'] ?? null)
            ->createdBy($validated['created_by'] ?? null)
            ->sortForAdminIndex($requestedSort, $requestedSort ? $direction : null)
            ->get();

        $categories = WorkshopCategory::query()->orderBy('name')->get();

        $creatorIds = Workshop::query()->distinct()->pluck('created_by')->filter();
        $creators = User::query()
            ->whereIn('id', $creatorIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $filters = [
            'status' => $requestedStatus,
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'starts_on' => $validated['starts_on'] ?? null,
            'created_by' => $validated['created_by'] ?? null,
            'sort' => $requestedSort,
            'direction' => $requestedSort ? $direction : null,
        ];

        return [
            'workshops' => $workshops,
            'filters' => $filters,
            'workshopTableColumns' => WorkshopTableColumns::adminTable($categories, $creators),
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
