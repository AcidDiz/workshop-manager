<?php

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Exceptions\Workshop\WorkshopRegistrationException;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Services\Workshop\WorkshopRegistrationService;
use Carbon\Carbon;

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

test('interval overlap helper detects overlapping ranges', function () {
    $service = app(WorkshopRegistrationService::class);
    $method = new \ReflectionMethod($service, 'workshopIntervalsOverlap');
    $method->setAccessible(true);

    $a = new Workshop([
        'starts_at' => Carbon::parse('2026-05-01 10:00:00'),
        'ends_at' => Carbon::parse('2026-05-01 12:00:00'),
    ]);
    $b = new Workshop([
        'starts_at' => Carbon::parse('2026-05-01 11:00:00'),
        'ends_at' => Carbon::parse('2026-05-01 13:00:00'),
    ]);

    expect($method->invoke($service, $a, $b))->toBeTrue();
});

test('interval overlap helper treats adjacent intervals as non overlapping', function () {
    $service = app(WorkshopRegistrationService::class);
    $method = new \ReflectionMethod($service, 'workshopIntervalsOverlap');
    $method->setAccessible(true);

    $a = new Workshop([
        'starts_at' => Carbon::parse('2026-05-01 10:00:00'),
        'ends_at' => Carbon::parse('2026-05-01 12:00:00'),
    ]);
    $b = new Workshop([
        'starts_at' => Carbon::parse('2026-05-01 12:00:00'),
        'ends_at' => Carbon::parse('2026-05-01 14:00:00'),
    ]);

    expect($method->invoke($service, $a, $b))->toBeFalse();
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
