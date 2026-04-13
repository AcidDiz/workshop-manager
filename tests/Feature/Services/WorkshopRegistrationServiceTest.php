<?php

use App\Exceptions\Workshop\WorkshopRegistrationException;
use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
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

    $service = new WorkshopRegistrationService;
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

    $service = new WorkshopRegistrationService;
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

    $service = new WorkshopRegistrationService;
    expect(fn () => $service->attach($employee, $workshop))
        ->toThrow(WorkshopRegistrationException::class, 'This workshop is no longer open for registration.');

    expect(WorkshopRegistration::query()->count())->toBe(0);
});

test('attach fails when confirmed seats equal capacity', function () {
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

    $service = new WorkshopRegistrationService;
    expect(fn () => $service->attach($employee, $workshop))
        ->toThrow(WorkshopRegistrationException::class, 'This workshop is full.');
});
