<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('admin sees the workshop overview dashboard without javascript errors', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    visit(route('admin.dashboard'))
        ->assertSee('Workshop overview')
        ->assertSee('Next upcoming workshop')
        ->assertSee('Last updated:')
        ->assertNoJavaScriptErrors();
});
