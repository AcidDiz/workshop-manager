<?php

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('employee can register from app workshops index', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'title' => 'Browser Register Workshop',
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($employee);

    visit(route('app.workshops.index'))
        ->assertSee('Browser Register Workshop')
        ->click('Register')
        ->assertSee('You are registered for this workshop.')
        ->assertNoJavaScriptErrors();

    expect(
        WorkshopRegistration::query()
            ->where('workshop_id', $workshop->id)
            ->where('user_id', $employee->id)
            ->exists()
    )->toBeTrue();
});

test('employee can cancel registration from app workshops with visible feedback', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'title' => 'Browser Cancel Workshop',
        'capacity' => 3,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $this->actingAs($employee);

    visit(route('app.workshops.index'))
        ->assertSee('Browser Cancel Workshop')
        ->click('Cancel registration')
        ->assertSee('Cancel registration?')
        ->click("@confirm-cancel-registration-{$workshop->id}")
        ->assertSee('Your registration has been cancelled.')
        ->assertNoJavaScriptErrors();

    expect(
        WorkshopRegistration::query()
            ->where('workshop_id', $workshop->id)
            ->where('user_id', $employee->id)
            ->exists()
    )->toBeFalse();
});

test('employee sees waiting list confirmation when joining a full workshop', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'title' => 'Browser Full Workshop',
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($employee);

    visit(route('app.workshops.index'))
        ->assertSee('Browser Full Workshop')
        ->assertSee('1/1')
        ->assertSee('Join waiting list')
        ->click("@join-waiting-list-{$workshop->id}")
        ->assertSee('You have been added to the waiting list.')
        ->assertNoJavaScriptErrors();
});

test('employee sees schedule overlap error when a conflicting workshop is open', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $starts = now()->addDays(10)->startOfHour();
    $first = Workshop::factory()->upcoming()->create([
        'title' => 'Overlap Workshop A',
        'starts_at' => $starts,
        'ends_at' => (clone $starts)->addHours(4),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $second = Workshop::factory()->upcoming()->create([
        'title' => 'Overlap Workshop B',
        'starts_at' => (clone $starts)->addHours(2),
        'ends_at' => (clone $starts)->addHours(6),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $first->id,
        'user_id' => $employee->id,
    ]);

    $this->actingAs($employee);

    visit(route('app.workshops.index'))
        ->assertSee('Overlap Workshop B')
        ->click('Register')
        ->assertSee('You already have a registration that overlaps this workshop time.')
        ->assertNoJavaScriptErrors();

    expect(
        WorkshopRegistration::query()
            ->where('workshop_id', $second->id)
            ->where('user_id', $employee->id)
            ->exists()
    )->toBeFalse();
});
