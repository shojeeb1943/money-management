<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $this->flag = storage_path('installed');
    @unlink($this->flag);
});

afterEach(function (): void {
    touch($this->flag);
});

test('the migrations step renders when the database connects', function (): void {
    $this->get(route('install.migrations'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('install/migrations'));
});

test('running migrations creates the schema and redirects to the admin step', function (): void {
    $this->post(route('install.migrations.run'))
        ->assertRedirect(route('install.admin'));

    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('companies'))->toBeTrue();
});

test('running migrations generates missing passport keys', function (): void {
    $keyDir = sys_get_temp_dir().'/moneta-passport-'.uniqid();
    File::ensureDirectoryExists($keyDir);
    Passport::loadKeysFrom($keyDir);

    try {
        $this->post(route('install.migrations.run'))
            ->assertRedirect(route('install.admin'))
            ->assertSessionHasNoErrors();

        expect(file_exists($keyDir.'/oauth-private.key'))->toBeTrue()
            ->and(file_exists($keyDir.'/oauth-public.key'))->toBeTrue();
    } finally {
        Passport::loadKeysFrom(storage_path());
        File::deleteDirectory($keyDir);
    }
});

test('the admin step renders once the schema exists', function (): void {
    $this->get(route('install.admin'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('install/admin'));
});
