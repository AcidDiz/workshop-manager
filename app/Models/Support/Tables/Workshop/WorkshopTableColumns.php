<?php

namespace App\Models\Support\Tables\Workshop;

use App\Enums\Workshop\WorkshopStatusEnum;
use App\Http\Resources\User\FilterSelectOptionResource as UserFilterSelectOptionResource;
use App\Http\Resources\WorkshopCategory\WorkshopCategoryFilterSelectOptionResource;
use App\Models\User;
use App\Models\WorkshopCategory;
use Illuminate\Support\Collection;

class WorkshopTableColumns
{
    /**
     * Admin index table: column metadata + filter wiring (filter_param is the query string key).
     *
     * @param  Collection<int, WorkshopCategory>  $categories
     * @param  Collection<int, User>  $creators
     * @return list<array<string, mixed>>
     */
    public static function adminTable(Collection $categories, Collection $creators): array
    {
        $categoryOptions = WorkshopCategoryFilterSelectOptionResource::collection($categories)->resolve();

        $creatorOptions = UserFilterSelectOptionResource::collection($creators)->resolve();

        $statusOptions = WorkshopStatusEnum::filterSelectOptions();

        return [
            [
                'field_name' => 'title',
                'label' => 'Title',
                'placeholder' => 'Filter by title',
                'cast_type' => 'string',
                'input_type' => 'text',
                'filterable' => true,
                'filter_param' => 'title',
                'sortable' => true,
                'default_sort' => 'asc',
            ],
            [
                'field_name' => 'category.name',
                'label' => 'Category',
                'placeholder' => 'Select Category',
                'cast_type' => 'string',
                'input_type' => 'select',
                'filterable' => true,
                'filter_param' => 'category_id',
                'options' => $categoryOptions,
                'sortable' => true,
                'default_sort' => 'asc',
            ],
            [
                'field_name' => 'starts_at',
                'label' => 'Starts at',
                'placeholder' => 'Date',
                'cast_type' => 'datetime',
                'input_type' => 'date',
                'filterable' => true,
                'filter_param' => 'starts_on',
                'sortable' => true,
                'default_sort' => 'asc',
            ],
            [
                'field_name' => 'enrollment',
                'label' => 'Enrollment',
                'placeholder' => null,
                'cast_type' => 'string',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
            [
                'field_name' => 'creator.name',
                'label' => 'Created by',
                'placeholder' => 'Select Author',
                'cast_type' => 'string',
                'input_type' => 'select',
                'filterable' => true,
                'filter_param' => 'created_by',
                'options' => $creatorOptions,
                'sortable' => true,
                'default_sort' => 'asc',
            ],
            [
                'field_name' => 'timing_status',
                'label' => 'Timing',
                'placeholder' => 'Select Status',
                'cast_type' => 'workshop_timing',
                'input_type' => 'select',
                'filterable' => true,
                'filter_param' => 'status',
                'options' => $statusOptions,
                'sortable' => true,
                'default_sort' => 'asc',
            ],
            [
                'field_name' => '_actions',
                'label' => 'Actions',
                'cast_type' => 'actions',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
        ];
    }
}
