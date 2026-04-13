<?php

namespace App\Support\Filters\Workshops;

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use App\Support\Tables\WorkshopTableColumns;
use Illuminate\Support\Collection;

class BuildWorkshopIndexData
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *   workshops: Collection<int, Workshop>,
     *   filters: array<string, mixed>,
     *   showWorkshopTable: bool,
     *   workshopTableColumns: list<array<string, mixed>>,
     *   employeeFilterFields: list<array<string, mixed>>
     * }
     */
    public function handle(User $user, array $validated): array
    {
        $requestedStatus = $validated['status'] ?? null;
        $effectiveStatus = $requestedStatus ?? ($user->can('workshops.manage') ? 'all' : 'upcoming');

        $showWorkshopTable = $user->can('workshops.manage');

        $requestedSort = $showWorkshopTable ? ($validated['sort'] ?? null) : null;
        $requestedDirection = $showWorkshopTable ? ($validated['direction'] ?? null) : null;
        $direction = $requestedDirection === 'desc' ? 'desc' : 'asc';

        $query = Workshop::query()
            ->withIndexRelations()
            ->status((string) $effectiveStatus)
            ->filterCategoryId($validated['category_id'] ?? null)
            ->searchTitle($validated['title'] ?? null)
            ->startsOn($validated['starts_on'] ?? null)
            ->when(
                $showWorkshopTable,
                fn ($query) => $query
                    ->createdBy($validated['created_by'] ?? null)
                    ->sortForAdminIndex($requestedSort, $requestedSort ? $direction : null),
                fn ($query) => $query->indexOrder(),
            );

        $workshops = $query->get();

        $categories = WorkshopCategory::query()->orderBy('name')->get();

        $creators = collect();
        if ($showWorkshopTable) {
            $creatorIds = Workshop::query()->distinct()->pluck('created_by')->filter();
            $creators = User::query()
                ->whereIn('id', $creatorIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $filters = [
            'status' => $requestedStatus,
            'category_id' => $validated['category_id'] ?? null,
            'title' => $validated['title'] ?? null,
            'starts_on' => $validated['starts_on'] ?? null,
            'created_by' => $showWorkshopTable ? ($validated['created_by'] ?? null) : null,
            'sort' => $showWorkshopTable ? $requestedSort : null,
            'direction' => $showWorkshopTable ? ($requestedSort ? $direction : null) : null,
        ];

        return [
            'workshops' => $workshops,
            'filters' => $filters,
            'showWorkshopTable' => $showWorkshopTable,
            'workshopTableColumns' => $showWorkshopTable
                ? WorkshopTableColumns::adminTable($categories, $creators)
                : [],
            'employeeFilterFields' => $showWorkshopTable
                ? []
                : WorkshopTableColumns::employeeFilters($categories),
        ];
    }
}
