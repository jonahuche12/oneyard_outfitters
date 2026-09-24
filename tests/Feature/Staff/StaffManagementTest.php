<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function staffPermission(string $slug): Permission
{
    return Permission::factory()->create([
        'name' => $slug,
        'slug' => $slug,
    ]);
}

function staffRoleWithPermissions(array $permissions): Role
{
    $role = Role::factory()->create();

    $permissionIds = collect($permissions)
        ->map(fn (string $permission) => staffPermission($permission)->id)
        ->all();

    $role->permissions()->sync($permissionIds);

    return $role;
}

it('allows a user with users.view to access staff', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $user->roles()->attach($role);

    $response = $this
        ->actingAs($user)
        ->get(route('staff.index'));

    $response->assertOk();
});

it('denies staff access without users.view', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('staff.index'));

    $response->assertForbidden();
});

it('allows authorized staff to create another staff account', function () {
    $role = staffRoleWithPermissions([
        'users.create',
        'users.assign-roles',
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $staffRole = Role::factory()->create([
        'name' => 'Sales',
        'slug' => 'sales',
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('staff.store'), [
            'name' => 'Jane Staff',
            'email' => 'jane@oneyard.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$staffRole->id],
        ]);

    $response->assertRedirect();

    $created = User::where('email', 'jane@oneyard.test')->first();

    expect($created)->not->toBeNull()
        ->and($created->is_active)->toBeTrue();

    expect($created->roles->pluck('id')->all())
        ->toContain($staffRole->id);
});

it('prevents unauthorized users from creating staff', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $staffRole = Role::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('staff.store'), [
            'name' => 'Unauthorized Staff',
            'email' => 'unauthorized@oneyard.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$staffRole->id],
        ]);

    $response->assertForbidden();

    expect(User::where('email', 'unauthorized@oneyard.test')->exists())
        ->toBeFalse();
});

it('prevents a staff member from deactivating their own account', function () {
    $role = staffRoleWithPermissions([
        'users.deactivate',
    ]);

    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $user->roles()->attach($role);

    $response = $this
        ->actingAs($user)
        ->patch(route('staff.deactivate', $user));

    $response->assertSessionHasErrors('staff');

    expect($user->fresh()->is_active)->toBeTrue();
});

it('allows an authorized administrator to deactivate staff', function () {
    $role = staffRoleWithPermissions([
        'users.deactivate',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->patch(route('staff.deactivate', $staff));

    $response->assertRedirect();

    expect($staff->fresh()->is_active)->toBeFalse();
});

it('allows an authorized administrator to activate staff', function () {
    $role = staffRoleWithPermissions([
        'users.activate',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => false,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->patch(route('staff.activate', $staff));

    $response->assertRedirect();

    expect($staff->fresh()->is_active)->toBeTrue();
});
it('allows an authorized user to access the staff creation form', function () {
    $role = staffRoleWithPermissions([
        'users.create',
    ]);

    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $user->roles()->attach($role);

    $response = $this
        ->actingAs($user)
        ->get(route('staff.create'));

    $response->assertOk();
    $response->assertSee('Create Staff Account');
});

it('denies access to the staff creation form without users.create', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('staff.create'));

    $response->assertForbidden();
});

it('allows an authorized user to view a staff account', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'name' => 'Jane Staff',
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.show', $staff));

    $response->assertOk();
    $response->assertSee('Jane Staff');
    $response->assertSee($staff->email);
});

it('denies viewing a staff account without users.view', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.show', $staff));

    $response->assertForbidden();
});

it('allows an authorized user to access the staff edit form', function () {
    $role = staffRoleWithPermissions([
        'users.update',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'name' => 'Jane Staff',
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.edit', $staff));

    $response->assertOk();
    $response->assertSee('Edit Staff');
    $response->assertSee('Jane Staff');
});

it('denies access to the staff edit form without users.update', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.edit', $staff));

    $response->assertForbidden();
});

it('allows an authorized user to update staff information', function () {
    $role = staffRoleWithPermissions([
        'users.update',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@oneyard.test',
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->put(route('staff.update', $staff), [
            'name' => 'Updated Staff',
            'email' => 'updated@oneyard.test',
            'password' => '',
            'password_confirmation' => '',
        ]);

    $response->assertRedirect(route('staff.show', $staff));

    $staff->refresh();

    expect($staff->name)->toBe('Updated Staff')
        ->and($staff->email)->toBe('updated@oneyard.test');
});

it('denies staff updates without users.update', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@oneyard.test',
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->put(route('staff.update', $staff), [
            'name' => 'Changed Name',
            'email' => 'changed@oneyard.test',
            'password' => '',
            'password_confirmation' => '',
        ]);

    $response->assertForbidden();

    $staff->refresh();

    expect($staff->name)->toBe('Original Name')
        ->and($staff->email)->toBe('original@oneyard.test');
});

it('validates required staff creation fields', function () {
    $role = staffRoleWithPermissions([
        'users.create',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->post(route('staff.store'), []);

    $response->assertSessionHasErrors([
        'name',
        'email',
        'password',
    ]);
});

it('rejects duplicate staff email addresses', function () {
    $role = staffRoleWithPermissions([
        'users.create',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    User::factory()->create([
        'email' => 'existing@oneyard.test',
    ]);

    $response = $this
        ->actingAs($admin)
        ->post(route('staff.store'), [
            'name' => 'Duplicate Staff',
            'email' => 'existing@oneyard.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

    $response->assertSessionHasErrors('email');
});

it('rejects duplicate email when updating another staff account', function () {
    $role = staffRoleWithPermissions([
        'users.update',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'email' => 'staff@oneyard.test',
    ]);

    User::factory()->create([
        'email' => 'existing@oneyard.test',
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->put(route('staff.update', $staff), [
            'name' => $staff->name,
            'email' => 'existing@oneyard.test',
            'password' => '',
            'password_confirmation' => '',
        ]);

    $response->assertSessionHasErrors('email');
});

it('allows an authorized user to change a staff password', function () {
    $role = staffRoleWithPermissions([
        'users.update',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'password' => 'old-password',
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->put(route('staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

    $response->assertRedirect();

    expect(password_verify('new-password123', $staff->fresh()->password))
        ->toBeTrue();
});

it('allows an authorized user to assign and replace staff roles', function () {
    $role = staffRoleWithPermissions([
        'users.update',
        'users.assign-roles',
    ]);

    $salesRole = Role::factory()->create([
        'name' => 'Sales',
        'slug' => 'sales',
    ]);

    $productionRole = Role::factory()->create([
        'name' => 'Production',
        'slug' => 'production',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);
    $staff->roles()->attach($salesRole);

    $response = $this
        ->actingAs($admin)
        ->put(route('staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => '',
            'password_confirmation' => '',
            'roles' => [$productionRole->id],
        ]);

    $response->assertRedirect();

    expect($staff->fresh()->roles->pluck('id')->all())
        ->toBe([$productionRole->id]);
});

it('does not allow role assignment when the administrator lacks users.assign-roles', function () {
    $role = staffRoleWithPermissions([
        'users.update',
    ]);

    $salesRole = Role::factory()->create([
        'name' => 'Sales',
        'slug' => 'sales',
    ]);

    $productionRole = Role::factory()->create([
        'name' => 'Production',
        'slug' => 'production',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);
    $staff->roles()->attach($salesRole);

    $response = $this
        ->actingAs($admin)
        ->put(route('staff.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'password' => '',
            'password_confirmation' => '',
            'roles' => [$productionRole->id],
        ]);

    $response->assertRedirect();

    expect($staff->fresh()->roles->pluck('id')->all())
        ->toBe([$salesRole->id]);
});

it('denies role assignment without users.assign-roles', function () {
    $role = staffRoleWithPermissions([
        'users.update',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.edit', $staff));

    $response->assertOk();
    $response->assertDontSee('Access & Roles');
});

it('denies staff deactivation without users.deactivate', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('staff.deactivate', $staff));

    $response->assertForbidden();

    expect($staff->fresh()->is_active)->toBeTrue();
});

it('denies staff activation without users.activate', function () {
    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'is_active' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('staff.activate', $staff));

    $response->assertForbidden();

    expect($staff->fresh()->is_active)->toBeFalse();
});

it('supports staff search by name', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    User::factory()->create([
        'name' => 'Jane Searchable',
        'email' => 'jane@oneyard.test',
    ]);

    User::factory()->create([
        'name' => 'John Other',
        'email' => 'john@oneyard.test',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.index', [
            'search' => 'Jane Searchable',
        ]));

    $response->assertOk();
    $response->assertSee('Jane Searchable');
    $response->assertDontSee('John Other');
});

it('supports staff search by email', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    User::factory()->create([
        'name' => 'Jane Search',
        'email' => 'jane.search@oneyard.test',
    ]);

    User::factory()->create([
        'name' => 'John Other',
        'email' => 'john.other@oneyard.test',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.index', [
            'search' => 'jane.search@oneyard.test',
        ]));

    $response->assertOk();
    $response->assertSee('Jane Search');
    $response->assertDontSee('John Other');
});

it('paginates staff accounts', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    User::factory()->count(16)->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.index'));

    $response->assertOk();
    $response->assertSee('page=2');
});

it('uses staff route model binding for show', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $staff = User::factory()->create([
        'name' => 'Bound Staff',
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->get("/staff/{$staff->id}");

    $response->assertOk();
    $response->assertSee('Bound Staff');
});

it('returns not found for a missing staff account', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->get('/staff/999999');

    $response->assertNotFound();
});

it('shows permission-aware staff actions on the index', function () {
    $role = staffRoleWithPermissions([
        'users.view',
        'users.update',
        'users.activate',
        'users.deactivate',
    ]);

    $admin = User::factory()->create([
        'is_active' => true,
    ]);

    $activeStaff = User::factory()->create([
        'name' => 'Active Staff',
        'is_active' => true,
    ]);

    $inactiveStaff = User::factory()->create([
        'name' => 'Inactive Staff',
        'is_active' => false,
    ]);

    $admin->roles()->attach($role);

    $response = $this
        ->actingAs($admin)
        ->get(route('staff.index'));

    $response->assertOk();
    $response->assertSee('Active Staff');
    $response->assertSee('Inactive Staff');
    $response->assertSee('Edit');
    $response->assertSee('Activate');
    $response->assertSee('Deactivate');
});

it('requires authentication for staff management routes', function () {
    $staff = User::factory()->create();

    $this->get(route('staff.index'))->assertRedirect();
    $this->get(route('staff.create'))->assertRedirect();
    $this->get(route('staff.show', $staff))->assertRedirect();
    $this->get(route('staff.edit', $staff))->assertRedirect();
});

it('prevents inactive users from accessing staff management', function () {
    $role = staffRoleWithPermissions([
        'users.view',
    ]);

    $user = User::factory()->create([
        'is_active' => false,
    ]);

    $user->roles()->attach($role);

    $response = $this
        ->actingAs($user)
        ->get(route('staff.index'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'Your account is inactive. Please contact an administrator.',
        ]);

    expect(auth()->check())->toBeFalse();
});

