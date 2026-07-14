<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->flag = storage_path('installed');
    @unlink($this->flag);
});

afterEach(function (): void {
    touch($this->flag);
});

test('web routes redirect to the installer when not installed', function (): void {
    $this->get('/login')->assertRedirect(route('install.index'));
    $this->get('/')->assertRedirect(route('install.index'));
});

test('the installer is reachable when not installed', function (): void {
    $this->get('/install')->assertOk();
});

test('the health endpoint is reachable when not installed', function (): void {
    $this->get('/up')->assertOk();
});

test('the installer redirects home when installed', function (): void {
    file_put_contents($this->flag, '{}');

    $this->get('/install')->assertRedirect('/');
    $this->get('/install/database')->assertRedirect('/');
});

test('web routes work normally when installed', function (): void {
    file_put_contents($this->flag, '{}');

    $this->get('/login')->assertOk();
});
