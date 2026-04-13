<?php

use App\Enums\WorkshopRegistrationStatus;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Illuminate\Database\QueryException;

test('workshop belongs to creator and lists registrations', function () {
    $creator = User::factory()->create();
    $attendee = User::factory()->create();

    $workshop = Workshop::create([
        'title' => 'Laravel basics',
        'description' => 'Intro',
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'capacity' => 10,
        'created_by' => $creator->id,
    ]);

    WorkshopRegistration::create([
        'workshop_id' => $workshop->id,
        'user_id' => $attendee->id,
        'status' => WorkshopRegistrationStatus::Confirmed,
    ]);

    expect($workshop->creator)->toBeInstanceOf(User::class)
        ->id->toBe($creator->id);

    expect($workshop->registrations)->toHaveCount(1)
        ->first()->user->id->toBe($attendee->id);

    expect($creator->createdWorkshops)->toHaveCount(1)
        ->first()->id->toBe($workshop->id);

    expect($attendee->workshopRegistrations)->toHaveCount(1)
        ->first()->workshop->id->toBe($workshop->id);
});

test('duplicate registration for same workshop and user violates unique constraint', function () {
    $user = User::factory()->create();
    $workshop = Workshop::create([
        'title' => 'Testing',
        'description' => null,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'capacity' => 5,
        'created_by' => User::factory()->create()->id,
    ]);

    WorkshopRegistration::create([
        'workshop_id' => $workshop->id,
        'user_id' => $user->id,
        'status' => WorkshopRegistrationStatus::Confirmed,
    ]);

    expect(fn () => WorkshopRegistration::create([
        'workshop_id' => $workshop->id,
        'user_id' => $user->id,
        'status' => WorkshopRegistrationStatus::WaitingList,
    ]))->toThrow(QueryException::class);
});

test('upcoming scope returns only workshops that have not started yet', function () {
    $creatorId = User::factory()->create()->id;

    Workshop::create([
        'title' => 'Past',
        'description' => null,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subHour(),
        'capacity' => 5,
        'created_by' => $creatorId,
    ]);

    $upcoming = Workshop::create([
        'title' => 'Upcoming',
        'description' => null,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'capacity' => 5,
        'created_by' => $creatorId,
    ]);

    $ids = Workshop::query()->upcoming()->pluck('id')->all();
    $pastId = Workshop::query()->where('title', 'Past')->value('id');

    expect($ids)->toContain($upcoming->id);
    expect($ids)->not->toContain($pastId);
});

test('ordered scope sorts workshops by starts_at ascending', function () {
    $creatorId = User::factory()->create()->id;

    $second = Workshop::create([
        'title' => 'B',
        'description' => null,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(3),
        'capacity' => 5,
        'created_by' => $creatorId,
    ]);

    $first = Workshop::create([
        'title' => 'A',
        'description' => null,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'capacity' => 5,
        'created_by' => $creatorId,
    ]);

    $ordered = Workshop::query()->ordered()->pluck('id')->all();

    expect($ordered[0])->toBe($first->id)
        ->and($ordered[1])->toBe($second->id);
});

test('confirmed and waiting list scopes filter registrations by status', function () {
    $workshop = Workshop::create([
        'title' => 'Mixed',
        'description' => null,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'capacity' => 10,
        'created_by' => User::factory()->create()->id,
    ]);

    WorkshopRegistration::create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
        'status' => WorkshopRegistrationStatus::Confirmed,
    ]);

    WorkshopRegistration::create([
        'workshop_id' => $workshop->id,
        'user_id' => User::factory()->create()->id,
        'status' => WorkshopRegistrationStatus::WaitingList,
    ]);

    expect(WorkshopRegistration::query()->confirmed()->count())->toBe(1)
        ->and(WorkshopRegistration::query()->waitingList()->count())->toBe(1);
});
