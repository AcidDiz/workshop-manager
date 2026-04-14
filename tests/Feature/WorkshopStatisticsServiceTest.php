<?php

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Services\Workshop\WorkshopStatisticsService;

test('statistics snapshot aggregates workshops and registrations', function () {
    $admin = User::factory()->create();
    $upcoming = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'capacity' => 12,
    ]);

    $later = Workshop::factory()->upcoming()->create([
        'starts_at' => $upcoming->starts_at->copy()->addDays(2),
        'ends_at' => $upcoming->ends_at->copy()->addDays(2),
        'created_by' => $admin->id,
    ]);

    $pastStart = now()->subDays(2);
    Workshop::factory()->create([
        'starts_at' => $pastStart,
        'ends_at' => (clone $pastStart)->addHours(3),
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create(['workshop_id' => $upcoming->id]);
    WorkshopRegistration::factory()->waitingList()->create(['workshop_id' => $upcoming->id]);

    $snap = app(WorkshopStatisticsService::class)->snapshot();

    expect($snap['workshops']['total'])->toBe(3)
        ->and($snap['workshops']['upcoming'])->toBe(2)
        ->and($snap['workshops']['closed'])->toBe(1)
        ->and($snap['registrations']['confirmed'])->toBe(1)
        ->and($snap['registrations']['waiting_list'])->toBe(1)
        ->and($snap['registrations']['total'])->toBe(2)
        ->and($snap['next_upcoming_workshop'])->not->toBeNull()
        ->and($snap['next_upcoming_workshop']['id'])->toBe($upcoming->id)
        ->and($snap['next_upcoming_workshop']['confirmed_registrations_count'])->toBe(1)
        ->and($snap['next_upcoming_workshop']['capacity'])->toBe(12);
});
