<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('user can log in and reach the dashboard', function () {
    $user = User::factory()->create();

    visit(route('login'))
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/dashboard')
        ->assertTitle('Dashboard')
        ->assertNoJavaScriptErrors();
});
