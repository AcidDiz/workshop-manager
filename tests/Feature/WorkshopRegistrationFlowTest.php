<?php

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('employee can register for an upcoming workshop with capacity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->post(route('app.workshops.registrations.attach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    $this->assertDatabaseHas('workshop_registrations', [
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
        'status' => WorkshopRegistrationStatusEnum::Confirmed->value,
    ]);
});

test('employee cannot register twice for the same workshop', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->post(route('app.workshops.registrations.attach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->where('user_id', $employee->id)->count())->toBe(1);
});

test('full workshop index row exposes confirmed count for card capacity state', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($employee)
        ->get(route('app.workshops.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('workshopList', 1)
            ->where('workshopList.0.id', $workshop->id)
            ->where('workshopList.0.confirmed_registrations_count', 1)
            ->where('workshopList.0.capacity', 1));
});

test('employee joins the waiting list when the workshop is full', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->post(route('app.workshops.registrations.attach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    $this->assertDatabaseHas('workshop_registrations', [
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
        'status' => WorkshopRegistrationStatusEnum::WaitingList->value,
    ]);
});

test('employee cannot register for a past workshop', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $startsAt = now()->subDays(2);
    $workshop = Workshop::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => (clone $startsAt)->addHours(3),
        'capacity' => 10,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->post(route('app.workshops.registrations.attach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->where('user_id', $employee->id)->count())->toBe(0);
});

test('employee can cancel a confirmed registration and free a seat', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 3,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->confirmed()->count())->toBe(1);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->delete(route('app.workshops.registrations.detach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->where('user_id', $employee->id)->count())->toBe(0)
        ->and(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->confirmed()->count())->toBe(0);
});

test('detach is idempotent when the user has no registration', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->delete(route('app.workshops.registrations.detach', $workshop))
        ->assertRedirect(route('app.workshops.index'));
});

test('user without workshops.view cannot attach or detach', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $stranger = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $this->actingAs($stranger)
        ->post(route('app.workshops.registrations.attach', $workshop))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete(route('app.workshops.registrations.detach', $workshop))
        ->assertForbidden();
});

test('confirmed cancellation promotes the first user on the waiting list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $holder = User::factory()->create();
    $holder->assignRole('employee');

    $firstWait = User::factory()->create();
    $firstWait->assignRole('employee');

    $secondWait = User::factory()->create();
    $secondWait->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $holder->id,
    ]);

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $firstWait->id,
        'created_at' => now()->subMinutes(10),
    ]);

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $secondWait->id,
        'created_at' => now()->subMinute(),
    ]);

    $this->actingAs($holder)
        ->from(route('app.workshops.index'))
        ->delete(route('app.workshops.registrations.detach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    expect(WorkshopRegistration::query()->where('user_id', $firstWait->id)->first()?->status)
        ->toBe(WorkshopRegistrationStatusEnum::Confirmed)
        ->and(WorkshopRegistration::query()->where('user_id', $secondWait->id)->first()?->status)
        ->toBe(WorkshopRegistrationStatusEnum::WaitingList);
});

test('employee cannot register for a workshop that overlaps another confirmed registration', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $startsA = now()->addDays(5)->startOfHour();
    $workshopA = Workshop::factory()->upcoming()->create([
        'starts_at' => $startsA,
        'ends_at' => (clone $startsA)->addHours(4),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $workshopB = Workshop::factory()->upcoming()->create([
        'starts_at' => (clone $startsA)->addHours(2),
        'ends_at' => (clone $startsA)->addHours(8),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshopA->id,
        'user_id' => $employee->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->post(route('app.workshops.registrations.attach', $workshopB))
        ->assertRedirect(route('app.workshops.index'));

    expect(WorkshopRegistration::query()->where('workshop_id', $workshopB->id)->where('user_id', $employee->id)->count())->toBe(0);
});

test('employee can leave the waiting list via detach', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
    ]);

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $this->actingAs($employee)
        ->from(route('app.workshops.index'))
        ->delete(route('app.workshops.registrations.detach', $workshop))
        ->assertRedirect(route('app.workshops.index'));

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->where('user_id', $employee->id)->count())->toBe(0);
});

test('app workshops index includes my_registration_status for the current user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $this->actingAs($employee)
        ->get(route('app.workshops.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('workshopList', 1)
            ->where('workshopList.0.id', $workshop->id)
            ->where('workshopList.0.my_registration_status', 'confirmed'));
});
