<?php

test('login page renders main auth copy', function () {
    visit(route('login'))
        ->assertTitle('Log in')
        ->assertSee('Email address')
        ->assertNoJavaScriptErrors();
});
