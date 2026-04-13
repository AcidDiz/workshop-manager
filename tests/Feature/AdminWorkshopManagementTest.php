<?php

use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use App\Models\WorkshopRegistration;
use App\Notifications\WorkshopReminderNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

afterEach(function (): void {
    Carbon::setTestNow(null);
});

test('admin can create a workshop with valid payload', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $category = WorkshopCategory::factory()->create();

    $starts = now()->addWeek()->startOfHour();
    $ends = (clone $starts)->addHours(3);

    $this->actingAs($admin)
        ->post(route('admin.workshops.store'), [
            'title' => 'New academy session',
            'description' => 'Hands-on exercises.',
            'workshop_category_id' => (string) $category->id,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $ends->format('Y-m-d\TH:i'),
            'capacity' => 12,
        ])
        ->assertRedirect(route('admin.workshops.index'));

    $workshop = Workshop::query()->where('title', 'New academy session')->first();

    expect($workshop)->not->toBeNull()
        ->and($workshop->created_by)->toBe($admin->id)
        ->and($workshop->workshop_category_id)->toBe($category->id)
        ->and($workshop->capacity)->toBe(12);
});

test('store rejects empty title', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $starts = now()->addWeek()->startOfHour();
    $ends = (clone $starts)->addHours(2);

    $this->actingAs($admin)
        ->post(route('admin.workshops.store'), [
            'title' => '',
            'description' => null,
            'workshop_category_id' => null,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $ends->format('Y-m-d\TH:i'),
            'capacity' => 10,
        ])
        ->assertSessionHasErrors('title');
});

test('store rejects ends_at before starts_at', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $starts = now()->addWeek()->startOfHour();
    $ends = (clone $starts)->subHour();

    $this->actingAs($admin)
        ->post(route('admin.workshops.store'), [
            'title' => 'Valid title',
            'description' => null,
            'workshop_category_id' => null,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $ends->format('Y-m-d\TH:i'),
            'capacity' => 10,
        ])
        ->assertSessionHasErrors('ends_at');
});

test('store rejects non positive capacity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $starts = now()->addWeek()->startOfHour();
    $ends = (clone $starts)->addHours(2);

    $this->actingAs($admin)
        ->post(route('admin.workshops.store'), [
            'title' => 'Valid title',
            'description' => null,
            'workshop_category_id' => null,
            'starts_at' => $starts->format('Y-m-d\TH:i'),
            'ends_at' => $ends->format('Y-m-d\TH:i'),
            'capacity' => 0,
        ])
        ->assertSessionHasErrors('capacity');
});

test('admin can update a workshop', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workshop = Workshop::factory()->upcoming()->create([
        'title' => 'Old title',
        'created_by' => $admin->id,
    ]);

    $starts = $workshop->starts_at->copy()->addDay();
    $ends = $workshop->ends_at->copy()->addDay();

    $this->actingAs($admin)
        ->put(route('admin.workshops.update', $workshop), [
            'title' => 'Revised title',
            'description' => 'Updated notes.',
            'workshop_category_id' => null,
            'starts_at' => $starts->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'ends_at' => $ends->timezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'capacity' => 25,
        ])
        ->assertRedirect(route('admin.workshops.index'));

    $workshop->refresh();

    expect($workshop->title)->toBe('Revised title')
        ->and($workshop->capacity)->toBe(25)
        ->and($workshop->description)->toBe('Updated notes.');
});

test('deleting a workshop cascades registrations', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
    ]);

    expect(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->count())->toBe(1);

    $this->actingAs($admin)
        ->delete(route('admin.workshops.destroy', $workshop))
        ->assertRedirect(route('admin.workshops.index'));

    expect(Workshop::query()->find($workshop->id))->toBeNull()
        ->and(WorkshopRegistration::query()->where('workshop_id', $workshop->id)->count())->toBe(0);
});

test('employee cannot access admin workshop management routes', function () {
    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => User::factory()->create()->id,
    ]);

    $this->actingAs($employee)
        ->get(route('admin.workshops.create'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->post(route('admin.workshops.store'), [])
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.workshops.show', $workshop))
        ->assertForbidden();

    $this->actingAs($employee)
        ->post(route('admin.workshops.participants.attach', $workshop), [
            'user_id' => $employee->id,
        ])
        ->assertForbidden();

    $this->actingAs($employee)
        ->delete(route('admin.workshops.participants.detach', $workshop), [
            'user_id' => $employee->id,
        ])
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.workshops.edit', $workshop))
        ->assertForbidden();

    $this->actingAs($employee)
        ->put(route('admin.workshops.update', $workshop), [])
        ->assertForbidden();

    $this->actingAs($employee)
        ->delete(route('admin.workshops.destroy', $workshop))
        ->assertForbidden();
});

test('admin can view workshop show with participants ordered confirmed before waiting list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'capacity' => 10,
    ]);

    $waitingUser = User::factory()->create(['name' => 'Waiter', 'email' => 'waiter@example.com']);
    $waitingUser->assignRole('employee');
    $confirmedUser = User::factory()->create(['name' => 'Joiner', 'email' => 'joiner@example.com']);
    $confirmedUser->assignRole('employee');

    WorkshopRegistration::factory()->waitingList()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $waitingUser->id,
        'created_at' => now()->subHour(),
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $confirmedUser->id,
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.workshops.show', $workshop))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/workshops/Show')
            ->where('workshop.id', $workshop->id)
            ->where('workshop.confirmed_registrations_count', 1)
            ->where('workshop.waiting_list_registrations_count', 1)
            ->has('participantList', 2)
            ->where('participantList.0.registration_status', 'confirmed')
            ->where('participantList.0.user.email', 'joiner@example.com')
            ->where('participantList.1.registration_status', 'waiting_list')
            ->where('participantList.1.user.email', 'waiter@example.com')
            ->has('participantTableColumns', 5)
            ->has('assignableUsers'));
});

test('admin attach participant rejects non-employee user_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $nonEmployee = User::factory()->create();
    $nonEmployee->assignRole('admin');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'capacity' => 5,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.workshops.show', $workshop))
        ->post(route('admin.workshops.participants.attach', $workshop), [
            'user_id' => $nonEmployee->id,
        ])
        ->assertRedirect(route('admin.workshops.show', $workshop))
        ->assertSessionHasErrors('user_id');

    expect(WorkshopRegistration::query()
        ->where('workshop_id', $workshop->id)
        ->where('user_id', $nonEmployee->id)
        ->exists())->toBeFalse();
});

test('admin attach participant rejects unknown user_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'capacity' => 5,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.workshops.show', $workshop))
        ->post(route('admin.workshops.participants.attach', $workshop), [
            'user_id' => 999_999,
        ])
        ->assertRedirect(route('admin.workshops.show', $workshop))
        ->assertSessionHasErrors('user_id');
});

test('admin attach adds employee to waiting list when workshop is full', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $firstEmployee = User::factory()->create();
    $firstEmployee->assignRole('employee');
    $secondEmployee = User::factory()->create();
    $secondEmployee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'capacity' => 1,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $firstEmployee->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.workshops.participants.attach', $workshop), [
            'user_id' => $secondEmployee->id,
        ])
        ->assertRedirect();

    $registration = WorkshopRegistration::query()
        ->where('workshop_id', $workshop->id)
        ->where('user_id', $secondEmployee->id)
        ->first();

    expect($registration)->not->toBeNull()
        ->and($registration->status->value)->toBe('waiting_list');
});

test('admin can attach and detach a workshop participant', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'capacity' => 5,
    ]);

    expect(WorkshopRegistration::query()
        ->where('workshop_id', $workshop->id)
        ->where('user_id', $employee->id)
        ->exists())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.workshops.participants.attach', $workshop), [
            'user_id' => $employee->id,
        ])
        ->assertRedirect();

    $registration = WorkshopRegistration::query()
        ->where('workshop_id', $workshop->id)
        ->where('user_id', $employee->id)
        ->first();

    expect($registration)->not->toBeNull()
        ->and($registration->status->value)->toBe('confirmed');

    $this->actingAs($admin)
        ->delete(route('admin.workshops.participants.detach', $workshop), [
            'user_id' => $employee->id,
        ])
        ->assertRedirect();

    expect(WorkshopRegistration::query()
        ->where('workshop_id', $workshop->id)
        ->where('user_id', $employee->id)
        ->exists())->toBeFalse();
});

test('admin create and edit pages render with categories', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    WorkshopCategory::factory()->create(['name' => 'Zeta track']);

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
        'workshop_category_id' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.workshops.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/workshops/Create')
            ->has('categories', 1));

    $this->actingAs($admin)
        ->get(route('admin.workshops.edit', $workshop))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/workshops/Edit')
            ->has('workshop')
            ->where('workshop.id', $workshop->id));
});

test('employee cannot dispatch next-day workshop reminders', function () {
    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $this->actingAs($employee)
        ->post(route('admin.workshops.reminders.next-day'))
        ->assertForbidden();
});

test('admin can dispatch next-day reminders for workshops starting tomorrow', function () {
    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00', 'UTC'));

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $tomorrow = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-06-11 09:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-06-11 12:00:00', 'UTC'),
        'created_by' => $admin->id,
    ]);

    $later = Workshop::factory()->create([
        'starts_at' => Carbon::parse('2026-06-12 09:00:00', 'UTC'),
        'ends_at' => Carbon::parse('2026-06-12 12:00:00', 'UTC'),
        'created_by' => $admin->id,
    ]);

    $confirmedTomorrow = User::factory()->create();
    $confirmedTomorrow->assignRole('employee');
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $tomorrow->id,
        'user_id' => $confirmedTomorrow->id,
    ]);

    $confirmedLater = User::factory()->create();
    $confirmedLater->assignRole('employee');
    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $later->id,
        'user_id' => $confirmedLater->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.workshops.reminders.next-day'))
        ->assertRedirect();

    Notification::assertSentToTimes($confirmedTomorrow, WorkshopReminderNotification::class, 1);
    Notification::assertNotSentTo($confirmedLater, WorkshopReminderNotification::class);
});

test('employee cannot dispatch reminders for a single workshop', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $this->actingAs($employee)
        ->post(route('admin.workshops.reminders.dispatch', $workshop))
        ->assertForbidden();
});

test('admin can dispatch reminders for a single upcoming workshop', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $participant = User::factory()->create();
    $participant->assignRole('employee');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $participant->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.workshops.reminders.dispatch', $workshop))
        ->assertRedirect();

    Notification::assertSentToTimes($participant, WorkshopReminderNotification::class, 1);
});

test('admin cannot dispatch reminders for a workshop that has already started', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $participant = User::factory()->create();
    $participant->assignRole('employee');

    $workshop = Workshop::factory()->create([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subDay()->addHours(2),
        'created_by' => $admin->id,
    ]);

    WorkshopRegistration::factory()->confirmed()->create([
        'workshop_id' => $workshop->id,
        'user_id' => $participant->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.workshops.reminders.dispatch', $workshop))
        ->assertRedirect();

    Notification::assertNothingSent();
});
