<?php

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Notifications\WorkshopReminderNotification;
use App\Services\Workshop\WorkshopReminderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

afterEach(function (): void {
    Carbon::setTestNow(null);
});

test('day-before reminders go only to confirmed participants for workshops starting the next calendar day', function () {
    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-04-14 12:00:00', 'UTC'));

    $tomorrowWorkshop = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-04-15 09:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-04-15 11:00:00', 'UTC'),
    ]);

    $laterWorkshop = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-04-16 09:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-04-16 11:00:00', 'UTC'),
    ]);

    $confirmed = User::factory()->create();
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $tomorrowWorkshop->id,
        'user_id' => $confirmed->id,
    ]);

    $waiting = User::factory()->create();
    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $tomorrowWorkshop->id,
        'user_id' => $waiting->id,
    ]);

    $otherDayConfirmed = User::factory()->create();
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $laterWorkshop->id,
        'user_id' => $otherDayConfirmed->id,
    ]);

    $count = app(WorkshopReminderService::class)->dispatchNextCalendarDayReminders(Carbon::now());

    expect($count)->toBe(1);

    Notification::assertSentTo($confirmed, WorkshopReminderNotification::class, function (WorkshopReminderNotification $notification) use ($tomorrowWorkshop): bool {
        return $notification->workshop->is($tomorrowWorkshop);
    });

    Notification::assertNotSentTo($waiting, WorkshopReminderNotification::class);
    Notification::assertNotSentTo($otherDayConfirmed, WorkshopReminderNotification::class);
});

test('workshops remind command sends next calendar day reminders', function () {
    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-05-01 08:00:00', 'UTC'));

    $workshop = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-05-02 12:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-05-02 14:00:00', 'UTC'),
    ]);

    $user = User::factory()->create();
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $user->id,
    ]);

    $this->artisan('workshops:remind')
        ->expectsOutputToContain('1 reminder(s) sent.')
        ->assertSuccessful();
});

test('day-before reminders are idempotent when the command runs twice the same day', function () {
    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-04-14 12:00:00', 'UTC'));

    $workshop = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-04-15 09:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-04-15 11:00:00', 'UTC'),
    ]);

    $user = User::factory()->create();
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $user->id,
    ]);

    $service = app(WorkshopReminderService::class);

    expect($service->dispatchNextCalendarDayReminders(Carbon::now()))->toBe(1);
    expect($service->dispatchNextCalendarDayReminders(Carbon::now()))->toBe(0);

    Notification::assertSentToTimes($user, WorkshopReminderNotification::class, 1);
});

test('admin manual reminders for a workshop are idempotent on the same calendar day', function () {
    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-06-01 10:00:00', 'UTC'));

    $workshop = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-06-05 09:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-06-05 11:00:00', 'UTC'),
    ]);

    $user = User::factory()->create();
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $user->id,
    ]);

    $service = app(WorkshopReminderService::class);

    expect($service->dispatchRemindersForWorkshop($workshop))->toBe(1);
    expect($service->dispatchRemindersForWorkshop($workshop))->toBe(0);

    Notification::assertSentToTimes($user, WorkshopReminderNotification::class, 1);
});
