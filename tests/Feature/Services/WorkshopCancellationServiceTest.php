<?php

use App\Enums\Workshop\WorkshopRegistrationStatusEnum;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Services\Workshop\WorkshopCancellationService;

test('detach removes an existing registration', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopCancellationService::class);
    $result = $service->detach($employee, $workshop);

    expect($result['removed'])->toBeTrue()
        ->and($result['previous_status'])->toBe(WorkshopRegistrationStatusEnum::Confirmed);
    expect(WorkshopRegistration::query()->count())->toBe(0);
});

test('detach returns was not registered when there is no row', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $service = app(WorkshopCancellationService::class);
    $result = $service->detach($employee, $workshop);

    expect($result['removed'])->toBeFalse()
        ->and($result['previous_status'])->toBeNull();
});

test('detaching confirmed promotes the first waiting list member fifo', function () {
    $admin = User::factory()->create();
    $holder = User::factory()->create();
    $firstWait = User::factory()->create();
    $secondWait = User::factory()->create();

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
        'created_at' => now()->subMinutes(5),
    ]);

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $secondWait->id,
        'created_at' => now()->subMinute(),
    ]);

    $service = app(WorkshopCancellationService::class);
    $service->detach($holder, $workshop);

    $firstRow = WorkshopRegistration::query()->where('user_id', $firstWait->id)->first();
    $secondRow = WorkshopRegistration::query()->where('user_id', $secondWait->id)->first();

    expect($firstRow)->not->toBeNull()
        ->and($firstRow->status)->toBe(WorkshopRegistrationStatusEnum::Confirmed)
        ->and($secondRow)->not->toBeNull()
        ->and($secondRow->status)->toBe(WorkshopRegistrationStatusEnum::WaitingList);
});

test('detaching waiting list does not promote anyone', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();
    $waiter = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'capacity' => 1,
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $admin->id,
    ]);

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $waiter->id,
    ]);

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $employee->id,
    ]);

    $service = app(WorkshopCancellationService::class);
    $service->detach($employee, $workshop);

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->waitingList()->count())->toBe(1)
        ->and(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->confirmed()->count())->toBe(1);
});
