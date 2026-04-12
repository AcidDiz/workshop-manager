<?php

use Illuminate\Foundation\Application;

test('application boots with a resolvable container', function () {
    expect(app())->toBeInstanceOf(Application::class)
        ->and(app()->version())->not->toBeEmpty();
});

test('health check endpoint is reachable', function () {
    $this->get('/up')->assertSuccessful();
});
