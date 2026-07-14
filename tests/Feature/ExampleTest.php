<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\User;

test('the home page redirects guests to login', function (): void {
    $this->get(route('home'))->assertRedirect('/login');
});

test('the home page and bare dashboard redirect users to their company dashboard', function (): void {
    $user = User::factory()->create();
    $company = resolve(CreateCompany::class)->handle($user, 'Acme Studio');
    $user->switchCompany($company);

    $this->actingAs($user)->get('/')->assertRedirect('/dashboard');
    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('dashboard', $company->slug));
});
