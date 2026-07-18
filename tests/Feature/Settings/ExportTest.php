<?php

declare(strict_types=1);

use App\Actions\Companies\CreateCompany;
use App\Models\User;

test('export page is displayed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('export.edit'))->assertOk();
});

test('a user without a current company cannot download an export', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('export.download'))->assertNotFound();
});

test('the current company data can be downloaded as an excel workbook', function (): void {
    $user = User::factory()->create();
    resolve(CreateCompany::class)->handle($user, 'Acme Studio');

    $response = $this->actingAs($user)->get(route('export.download'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
