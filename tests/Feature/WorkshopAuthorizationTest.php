<?php

use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('authenticated users without workshop view permission cannot access the workshops index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('app.workshops.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.workshops.index'))
        ->assertForbidden();
});

test('employees with workshops.view may access the workshops index', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');

    Workshop::factory()->upcoming()->create([
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('app.workshops.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('workshopTableColumns')
            ->has('cardFilterFields'));
});

test('admins with workshops.manage receive table mode and column metadata', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.workshops.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/workshops/Index')
            ->missing('cardFilterFields')
            ->where('filters.status', null)
            ->has('workshopTableColumns')
            ->where('workshopTableColumns.0.field_name', 'title')
            ->where('workshopTableColumns.6.field_name', '_actions')
            ->where('workshopTableColumns.6.cast_type', 'actions'));
});

test('admins can sort the workshop list via query string', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Workshop::factory()->upcoming()->create([
        'title' => 'AAA',
        'created_by' => $admin->id,
    ]);
    Workshop::factory()->upcoming()->create([
        'title' => 'ZZZ',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.workshops.index', ['sort' => 'title', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'title')
            ->where('filters.direction', 'desc')
            ->where('workshopList.0.title', 'ZZZ'));
});

test('admins are redirected from the app workshops index to the admin index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('app.workshops.index', ['sort' => 'title', 'direction' => 'desc']))
        ->assertRedirect(route('admin.workshops.index', ['sort' => 'title', 'direction' => 'desc']));
});

test('workshop policy grants list access only when the user can view workshops', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole('employee');

    $stranger = User::factory()->create();

    expect($viewer->can('viewAny', Workshop::class))->toBeTrue()
        ->and($stranger->can('viewAny', Workshop::class))->toBeFalse();
});

test('shared inertia auth exposes workshop permission flags', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.workshop_permissions.view', true)
            ->where('auth.workshop_permissions.manage', true));

    $this->actingAs($employee)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.workshop_permissions.view', true)
            ->where('auth.workshop_permissions.manage', false));
});

test('workshop policy ties mutations to workshops.manage', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    expect($admin->can('create', Workshop::class))->toBeTrue()
        ->and($admin->can('update', $workshop))->toBeTrue()
        ->and($employee->can('create', Workshop::class))->toBeFalse()
        ->and($employee->can('update', $workshop))->toBeFalse();
});
