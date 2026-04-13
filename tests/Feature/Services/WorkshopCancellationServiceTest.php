<?php

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

    $service = new WorkshopCancellationService;
    $removed = $service->detach($employee, $workshop);

    expect($removed)->toBeTrue();
    expect(WorkshopRegistration::query()->count())->toBe(0);
});

test('detach returns was not registered when there is no row', function () {
    $admin = User::factory()->create();
    $employee = User::factory()->create();

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $service = new WorkshopCancellationService;
    $removed = $service->detach($employee, $workshop);

    expect($removed)->toBeFalse();
});
