<?php

use App\Models\User;
use App\Models\Workshop;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the workshops index', function () {
    $this->get(route('app.workshops.index'))->assertRedirect(route('login'));
    $this->get(route('admin.workshops.index'))->assertRedirect(route('login'));
});

test('authenticated users with workshops.view can view the workshops index', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('employee');

    Workshop::factory()->upcoming()->create([
        'title' => 'Visible session',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('app.workshops.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/workshops/Index')
            ->has('workshopList', 1)
            ->where('workshopList.0.title', 'Visible session')
            ->where('workshopList.0.my_registration_status', null)
            ->where('filters.status', null)
            ->has('cardFilterFields', 3));
});
