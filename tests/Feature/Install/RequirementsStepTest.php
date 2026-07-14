<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->flag = storage_path('installed');
    @unlink($this->flag);
});

afterEach(function (): void {
    touch($this->flag);
});

test('the requirements step reports server status', function (): void {
    $this->get(route('install.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('install/requirements')
            ->where('requirements.php.passes', true)
            ->where('requirements.passes', true)
            ->has('requirements.extensions')
            ->has('requirements.drivers')
            ->has('requirements.paths'));
});

test('the database step redirects back when requirements fail', function (): void {
    config(['installer.php_version' => '99.0']);

    $this->get(route('install.database'))->assertRedirect(route('install.index'));
});

test('the database step renders when requirements pass', function (): void {
    $this->get(route('install.database'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('install/database'));
});
