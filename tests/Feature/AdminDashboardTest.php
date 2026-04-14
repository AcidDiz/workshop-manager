<?php

use App\Events\Workshop\WorkshopAdminStatisticsUpdated;
use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('guests cannot access the admin workshop dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('employees without manage permission cannot access the admin workshop dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admins receive the dashboard page with statistics props', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $workshop = Workshop::factory()->upcoming()->create([
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard/Index')
            ->has('statistics')
            ->where('statistics.workshops.total', 1)
            ->where('statistics.workshops.upcoming', 1)
            ->where('statistics.registrations.confirmed', 0)
            ->where('statistics.next_upcoming_workshop.id', $workshop->id)
            ->has('statistics.generated_at'));
});

test('admin statistics updates broadcast immediately over reverb', function () {
    expect(new WorkshopAdminStatisticsUpdated([]))
        ->toBeInstanceOf(ShouldBroadcastNow::class);
});

test('workshop description update does not broadcast admin statistics', function () {
    $workshop = Workshop::factory()->upcoming()->create(['description' => 'original']);

    Event::fake([WorkshopAdminStatisticsUpdated::class]);

    $workshop->update(['description' => 'revised']);

    Event::assertNotDispatched(WorkshopAdminStatisticsUpdated::class);
});

test('workshop starts_at update broadcasts admin statistics', function () {
    $workshop = Workshop::factory()->upcoming()->create();

    Event::fake([WorkshopAdminStatisticsUpdated::class]);

    $newStart = $workshop->starts_at->copy()->addDays(2);
    $workshop->update([
        'starts_at' => $newStart,
        'ends_at' => $newStart->copy()->addHours(2),
    ]);

    Event::assertDispatched(WorkshopAdminStatisticsUpdated::class);
});
