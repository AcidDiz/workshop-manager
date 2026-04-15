<?php

namespace App\Models\Support\Tables\Workshop;

/**
 * Column metadata for admin workshop detail: registered users (confirmed and waiting list).
 *
 * @return list<array<string, mixed>>
 */
class WorkshopRegistrationTableColumns
{
    public static function adminShowTable(): array
    {
        return [
            [
                'field_name' => 'user.name',
                'label' => 'Name',
                'placeholder' => null,
                'cast_type' => 'string',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
            [
                'field_name' => 'user.email',
                'label' => 'Email',
                'placeholder' => null,
                'cast_type' => 'string',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
            [
                'field_name' => 'registration_status_label',
                'label' => 'Status',
                'placeholder' => null,
                'cast_type' => 'string',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
            [
                'field_name' => 'created_at',
                'label' => 'Registered at',
                'placeholder' => null,
                'cast_type' => 'datetime',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
            [
                'field_name' => '_actions',
                'label' => 'Actions',
                'placeholder' => null,
                'cast_type' => 'actions',
                'input_type' => null,
                'filterable' => false,
                'sortable' => false,
                'default_sort' => null,
            ],
        ];
    }
}
