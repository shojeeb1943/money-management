<?php

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the companies index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('companies.index'));

    $response->assertOk();
});

test('companies can be created', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Test Company',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('companies', [
        'name' => 'Test Company',
        'is_personal' => false,
    ]);
});

test('company slug uses next available suffix', function () {
    $user = User::factory()->create();

    Company::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Company::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Company::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this
        ->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Acme',
        ]);

    $this->assertDatabaseHas('companies', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('the company edit page can be rendered', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $company->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->get(route('companies.edit', $company));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/edit')
            ->where('company.slug', $company->slug)
            ->where('permissions.canUpdateCompany', true),
        );
});

test('companies can be updated by owners', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['name' => 'Original Name']);

    $company->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->patch(route('companies.update', $company), [
            'name' => 'Updated Name',
        ]);

    $response->assertRedirect(route('companies.edit', $company->fresh()));

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'name' => 'Updated Name',
    ]);
});

test('companies cannot be updated by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $company = Company::factory()->create();

    $company->members()->attach($owner, ['role' => CompanyRole::Owner->value]);
    $company->members()->attach($member, ['role' => CompanyRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->patch(route('companies.update', $company), [
            'name' => 'Updated Name',
        ]);

    $response->assertForbidden();
});

test('companies can be deleted by owners', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $company->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);
});

test('company deletion requires name confirmation', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $company->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => 'Wrong Name',
        ]);

    $response->assertSessionHasErrors('name');

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'deleted_at' => null,
    ]);
});

test('deleting current company switches to alphabetically first remaining company', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluCompany = Company::factory()->create(['name' => 'Zulu Company']);
    $zuluCompany->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $alphaCompany = Company::factory()->create(['name' => 'Alpha Company']);
    $alphaCompany->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $betaCompany = Company::factory()->create(['name' => 'Beta Company']);
    $betaCompany->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $user->update(['current_company_id' => $zuluCompany->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $zuluCompany), [
            'name' => $zuluCompany->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $zuluCompany->id,
    ]);

    expect($user->fresh()->current_company_id)->toEqual($alphaCompany->id);
});

test('deleting current company falls back to personal company when alphabetically first', function () {
    $user = User::factory()->create();
    $personalCompany = $user->personalCompany();
    $company = Company::factory()->create(['name' => 'Zulu Company']);
    $company->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $user->update(['current_company_id' => $company->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);

    expect($user->fresh()->current_company_id)->toEqual($personalCompany->id);
});

test('deleting non current company leaves current company unchanged', function () {
    $user = User::factory()->create();
    $personalCompany = $user->personalCompany();
    $company = Company::factory()->create();
    $company->members()->attach($user, ['role' => CompanyRole::Owner->value]);

    $user->update(['current_company_id' => $personalCompany->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);

    expect($user->fresh()->current_company_id)->toEqual($personalCompany->id);
});

test('personal companies cannot be deleted', function () {
    $user = User::factory()->create();

    $personalCompany = $user->personalCompany();

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $personalCompany), [
            'name' => $personalCompany->name,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('companies', [
        'id' => $personalCompany->id,
        'deleted_at' => null,
    ]);
});

test('companies cannot be deleted by non owners', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $company = Company::factory()->create();

    $company->members()->attach($owner, ['role' => CompanyRole::Owner->value]);
    $company->members()->attach($member, ['role' => CompanyRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertForbidden();
});

test('users can switch companies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $company->members()->attach($user, ['role' => CompanyRole::Member->value]);

    $response = $this
        ->actingAs($user)
        ->post(route('companies.switch', $company));

    $response->assertRedirect();

    expect($user->fresh()->current_company_id)->toEqual($company->id);
});

test('users cannot switch to company they dont belong to', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('companies.switch', $company));

    $response->assertForbidden();
});

test('guests cannot access companies', function () {
    $response = $this->get(route('companies.index'));

    $response->assertRedirect(route('login'));
});
