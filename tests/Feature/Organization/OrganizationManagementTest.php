<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function organizationPermission(string $slug): Permission
{
    return Permission::factory()->create([
        'name' => $slug,
        'slug' => $slug,
    ]);
}

function organizationRoleWithPermissions(array $permissions): Role
{
    $role = Role::factory()->create();

    $permissionIds = collect($permissions)
        ->map(fn (string $permission) => organizationPermission($permission)->id)
        ->all();

    $role->permissions()->sync($permissionIds);

    return $role;
}

function organizationUserWithPermissions(array $permissions): User
{
    $role = organizationRoleWithPermissions($permissions);

    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $user->roles()->attach($role);

    return $user;
}


function organizationUserWithRole(
    string $roleSlug,
    string $roleName,
    array $permissions
): User {
    $role = Role::factory()->create([
        'name' => $roleName,
        'slug' => $roleSlug,
    ]);

    $permissionIds = collect($permissions)
        ->map(fn (string $permission) => organizationPermission($permission)->id)
        ->all();

    $role->permissions()->sync($permissionIds);

    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $user->roles()->attach($role);

    return $user;
}

it('allows a user with organizations.view to access organizations', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index'));

    $response->assertOk();
});

it('denies organization access without organizations.view', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index'));

    $response->assertForbidden();
});

it('requires authentication for organization management routes', function () {
    $organization = Organization::factory()->create();

    $this->get(route('organizations.index'))->assertRedirect();
    $this->get(route('organizations.create'))->assertRedirect();
    $this->get(route('organizations.show', $organization))->assertRedirect();
    $this->get(route('organizations.edit', $organization))->assertRedirect();
});

it('prevents inactive users from accessing organizations', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $user->update([
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Your account is inactive. Please contact an administrator.',
        ]);

    expect(auth()->check())->toBeFalse();
});

it('allows an authorized user to access the organization creation form', function () {
    $user = organizationUserWithPermissions([
        'organizations.create',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.create'));

    $response->assertOk();
});

it('denies access to the organization creation form without organizations.create', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.create'));

    $response->assertForbidden();
});

it('allows an authorized user to create an organization', function () {
    $user = organizationUserWithPermissions([
        'organizations.create',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Example Academy',
            'type' => 'school',
            'ownership' => 'private',
            'phone' => '08012345678',
            'email' => 'admin@example.test',
            'address' => '12 School Road',
            'city' => 'Aba',
            'area' => 'Ariaria',
            'lga' => 'Aba South',
            'state' => 'Abia',
            'country' => 'Nigeria',
            'website' => 'https://example.test',
            'notes' => 'Example organization.',
        ]);

    $response->assertRedirect();

    $organization = Organization::query()
        ->where('name', 'Example Academy')
        ->first();

    expect($organization)->not->toBeNull()
        ->and($organization->organization_code)->toBe(
            sprintf('ORG-%06d', $organization->id)
        )
        ->and($organization->is_active)->toBeTrue();

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'name' => 'Example Academy',
        'type' => 'school',
        'ownership' => 'private',
        'organization_code' => sprintf(
            'ORG-%06d',
            $organization->id
        ),
    ]);
});

it('prevents unauthorized users from creating organizations', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Unauthorized Organization',
            'type' => 'company',
            'ownership' => 'private',
        ]);

    $response->assertForbidden();

    expect(
        Organization::where(
            'name',
            'Unauthorized Organization'
        )->exists()
    )->toBeFalse();
});

it('validates required organization creation fields', function () {
    $user = organizationUserWithPermissions([
        'organizations.create',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.store'), []);

    $response->assertSessionHasErrors([
        'name',
        'type',
        'ownership',
    ]);
});

it('rejects invalid organization type', function () {
    $user = organizationUserWithPermissions([
        'organizations.create',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Invalid Organization',
            'type' => 'invalid-type',
            'ownership' => 'private',
        ]);

    $response->assertSessionHasErrors('type');
});

it('rejects invalid organization ownership', function () {
    $user = organizationUserWithPermissions([
        'organizations.create',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.store'), [
            'name' => 'Invalid Organization',
            'type' => 'school',
            'ownership' => 'invalid-ownership',
        ]);

    $response->assertSessionHasErrors('ownership');
});

it('allows an authorized user to view an organization', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Example Academy',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.show', $organization));

    $response->assertOk();
});

it('denies viewing an organization without organizations.view', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $organization = Organization::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.show', $organization));

    $response->assertForbidden();
});

it('allows an authorized user to access the organization edit form', function () {
    $user = organizationUserWithPermissions([
        'organizations.update',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Example Academy',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.edit', $organization));

    $response->assertOk();
});

it('denies access to the organization edit form without organizations.update', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.edit', $organization));

    $response->assertForbidden();
});

it('allows an authorized user to update organization information', function () {
    $user = organizationUserWithPermissions([
        'organizations.update',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Original Organization',
        'type' => 'school',
        'ownership' => 'private',
    ]);

    $originalCode = $organization->organization_code;

    $response = $this
        ->actingAs($user)
        ->put(route('organizations.update', $organization), [
            'name' => 'Updated Organization',
            'type' => 'company',
            'ownership' => 'public',
            'phone' => '08098765432',
            'email' => 'updated@example.test',
            'city' => 'Port Harcourt',
            'state' => 'Rivers',
            'country' => 'Nigeria',
        ]);

    $response->assertRedirect(
        route('organizations.show', $organization)
    );

    $organization->refresh();

    expect($organization->name)->toBe('Updated Organization')
        ->and($organization->type)->toBe('company')
        ->and($organization->ownership)->toBe('public')
        ->and($organization->organization_code)->toBe($originalCode);
});

it('denies organization updates without organizations.update', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Original Organization',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('organizations.update', $organization), [
            'name' => 'Changed Organization',
            'type' => 'company',
            'ownership' => 'private',
        ]);

    $response->assertForbidden();

    expect($organization->fresh()->name)
        ->toBe('Original Organization');
});

it('preserves the organization code when organization details are updated', function () {
    $user = organizationUserWithPermissions([
        'organizations.update',
    ]);

    $organization = Organization::factory()->create([
        'organization_code' => 'ORG-000123',
        'name' => 'Original Organization',
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('organizations.update', $organization), [
            'name' => 'Updated Organization',
            'type' => 'ngo',
            'ownership' => 'non_profit',
        ]);

    $response->assertRedirect();

    expect($organization->fresh()->organization_code)
        ->toBe('ORG-000123');
});

it('allows an administrator to deactivate an organization', function () {
    $user = organizationUserWithRole(
        'admin',
        'Administrator',
        ['organizations.update']
    );

    $organization = Organization::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.deactivate', $organization));

    $response->assertRedirect();

    expect($organization->fresh()->is_active)->toBeFalse();
});

it('allows an administrator to activate an organization', function () {
    $user = organizationUserWithRole(
        'admin',
        'Administrator',
        ['organizations.update']
    );

    $organization = Organization::factory()->create([
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.activate', $organization));

    $response->assertRedirect();

    expect($organization->fresh()->is_active)->toBeTrue();
});

it('allows a super admin to deactivate an organization', function () {
    $user = organizationUserWithRole(
        'super-admin',
        'Super Admin',
        ['organizations.update']
    );

    $organization = Organization::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.deactivate', $organization));

    $response->assertRedirect();

    expect($organization->fresh()->is_active)->toBeFalse();
});

it('allows a super admin to activate an organization', function () {
    $user = organizationUserWithRole(
        'super-admin',
        'Super Admin',
        ['organizations.update']
    );

    $organization = Organization::factory()->create([
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.activate', $organization));

    $response->assertRedirect();

    expect($organization->fresh()->is_active)->toBeTrue();
});

it('denies organization deactivation to sales even with organizations.update', function () {
    $user = organizationUserWithRole(
        'sales',
        'Sales',
        ['organizations.update']
    );

    $organization = Organization::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.deactivate', $organization));

    $response->assertForbidden();

    expect($organization->fresh()->is_active)->toBeTrue();
});

it('denies organization activation to sales even with organizations.update', function () {
    $user = organizationUserWithRole(
        'sales',
        'Sales',
        ['organizations.update']
    );

    $organization = Organization::factory()->create([
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.activate', $organization));

    $response->assertForbidden();

    expect($organization->fresh()->is_active)->toBeFalse();
});

it('denies organization deactivation without organizations.update', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.deactivate', $organization));

    $response->assertForbidden();

    expect($organization->fresh()->is_active)->toBeTrue();
});

it('denies organization activation without organizations.update', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create([
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('organizations.activate', $organization));

    $response->assertForbidden();

    expect($organization->fresh()->is_active)->toBeFalse();
});

it('supports organization search by code', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->create([
        'organization_code' => 'ORG-000111',
        'name' => 'First Organization',
    ]);

    Organization::factory()->create([
        'organization_code' => 'ORG-000222',
        'name' => 'Second Organization',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index', [
            'search' => 'ORG-000111',
        ]));

    $response->assertOk();
    $response->assertSee('First Organization');
    $response->assertDontSee('Second Organization');
});

it('supports organization search by name', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->create([
        'name' => 'Aba International School',
    ]);

    Organization::factory()->create([
        'name' => 'Rivers Manufacturing Company',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index', [
            'search' => 'Aba International School',
        ]));

    $response->assertOk();
    $response->assertSee('Aba International School');
    $response->assertDontSee('Rivers Manufacturing Company');
});

it('supports organization search by type', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->create([
        'name' => 'Example School',
        'type' => 'school',
    ]);

    Organization::factory()->create([
        'name' => 'Example Company',
        'type' => 'company',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index', [
            'search' => 'school',
        ]));

    $response->assertOk();
    $response->assertSee('Example School');
    $response->assertDontSee('Example Company');
});

it('supports organization search by city', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->create([
        'name' => 'Aba Organization',
        'city' => 'Aba',
    ]);

    Organization::factory()->create([
        'name' => 'Lagos Organization',
        'city' => 'Lagos',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index', [
            'search' => 'Aba',
        ]));

    $response->assertOk();
    $response->assertSee('Aba Organization');
    $response->assertDontSee('Lagos Organization');
});

it('supports organization search by state', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->create([
        'name' => 'Abia Organization',
        'state' => 'Abia',
    ]);

    Organization::factory()->create([
        'name' => 'Rivers Organization',
        'state' => 'Rivers',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index', [
            'search' => 'Abia',
        ]));

    $response->assertOk();
    $response->assertSee('Abia Organization');
    $response->assertDontSee('Rivers Organization');
});

it('paginates organizations', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->count(16)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index'));

    $response->assertOk();
    $response->assertSee('page=2');
});

it('preserves organization search during pagination', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    Organization::factory()->count(16)->create([
        'city' => 'Aba',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('organizations.index', [
            'search' => 'Aba',
        ]));

    $response->assertOk();
    $response->assertSee('search=Aba');
    $response->assertSee('page=2');
});

it('uses organization route model binding for show', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Bound Organization',
    ]);

    $response = $this
        ->actingAs($user)
        ->get("/organizations/{$organization->id}");

    $response->assertOk();
});

it('returns not found for a missing organization', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/organizations/999999');

    $response->assertNotFound();
});

it('does not expose soft deleted organizations through normal route model binding', function () {
    $user = organizationUserWithPermissions([
        'organizations.view',
    ]);

    $organization = Organization::factory()->create([
        'name' => 'Deleted Organization',
    ]);

    $organization->delete();

    $this
        ->actingAs($user)
        ->get(route('organizations.show', $organization->id))
        ->assertNotFound();

    $this
        ->actingAs($user)
        ->get(route('organizations.index'))
        ->assertDontSee('Deleted Organization');
});
