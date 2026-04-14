<?php

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Exceptions\Workshop\WorkshopRegistrationException;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Services\Workshop\WorkshopRegistrationService;

test('attach creates a confirmed registration when there is capacity', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 4,
        'created_by' => $admin->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    $registration = $service->attach($employee, $workshop);

    expect($registration->status)->toBe(WorkshopRegistrationStatusEnum::Confirmed);

    expect(WorkshopRegistration::query()->count())->toBe(1);
});

test('attach fails when the user is already registered', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 4,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    expect(fn () => $service->attach($employee, $workshop))
        ->toThrow(WorkshopRegistrationException::class, 'You are already registered for this workshop.');

    expect(WorkshopRegistration::query()->count())->toBe(1);
});

test('attach fails when the workshop has started', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $startsAt = now()->subDay();
    $workshop = Workshop::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => (clone $startsAt)->addHours(2),
        'capacity' => 4,
        'created_by' => $admin->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    expect(fn () => $service->attach($employee, $workshop))
        ->toThrow(WorkshopRegistrationException::class, 'This workshop is no longer open for registration.');

    expect(WorkshopRegistration::query()->count())->toBe(0);
});

test('attach creates a waiting list registration when confirmed seats equal capacity', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    $registration = $service->attach($employee, $workshop);

    expect($registration->status)->toBe(WorkshopRegistrationStatusEnum::WaitingList);
    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->count())->toBe(2);
});

test('attach fails when another registration overlaps in time', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $startsA = now()->addDays(3)->startOfHour();
    $workshopA = Workshop::factory()->upcoming()->create([
        'starts_at' => $startsA,
        'ends_at' => (clone $startsA)->addHours(4),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $workshopB = Workshop::factory()->upcoming()->create([
        'starts_at' => (clone $startsA)->addHours(2),
        'ends_at' => (clone $startsA)->addHours(6),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshopA->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    expect(fn () => $service->attach($employee, $workshopB))
        ->toThrow(WorkshopRegistrationException::class, 'You already have a registration that overlaps this workshop time.');

    expect(WorkshopRegistration::query()->where('user_id', $employee->id)->count())->toBe(1);
});

test('attach allows non overlapping workshops for the same user', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $startsA = now()->addDays(3)->startOfHour();
    $workshopA = Workshop::factory()->upcoming()->create([
        'starts_at' => $startsA,
        'ends_at' => (clone $startsA)->addHours(2),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $workshopB = Workshop::factory()->upcoming()->create([
        'starts_at' => (clone $startsA)->addHours(2),
        'ends_at' => (clone $startsA)->addHours(5),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshopA->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    $second = $service->attach($employee, $workshopB);

    expect($second->status)->toBe(WorkshopRegistrationStatusEnum::Confirmed);
    expect(WorkshopRegistration::query()->where('user_id', $employee->id)->count())->toBe(2);
});

test('attachAsAdmin allows registration when the workshop is in the past', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $startsAt = now()->subDay();
    $workshop = Workshop::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => (clone $startsAt)->addHours(2),
        'capacity' => 4,
        'created_by' => $admin->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    $registration = $service->attachAsAdmin($employee, $workshop);

    expect($registration->status)->toBe(WorkshopRegistrationStatusEnum::Confirmed);
});

test('attachAsAdmin fails when there are no free confirmed seats', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    expect(fn () => $service->attachAsAdmin($employee, $workshop))
        ->toThrow(
            WorkshopRegistrationException::class,
            'This workshop is at capacity. Remove a participant or increase capacity before adding another.'
        );
});

test('attachAsAdmin fails when the subject is already registered', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 4,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    expect(fn () => $service->attachAsAdmin($employee, $workshop))
        ->toThrow(WorkshopRegistrationException::class, 'This user is already registered for this workshop.');
});

test('attachAsAdmin fails when another registration overlaps in time', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $startsA = now()->addDays(3)->startOfHour();
    $workshopA = Workshop::factory()->upcoming()->create([
        'starts_at' => $startsA,
        'ends_at' => (clone $startsA)->addHours(4),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    $workshopB = Workshop::factory()->upcoming()->create([
        'starts_at' => (clone $startsA)->addHours(2),
        'ends_at' => (clone $startsA)->addHours(6),
        'capacity' => 5,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshopA->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopRegistrationService::class);
    expect(fn () => $service->attachAsAdmin($employee, $workshopB))
        ->toThrow(WorkshopRegistrationException::class, 'This user already has another registration overlapping this workshop time.');
});
