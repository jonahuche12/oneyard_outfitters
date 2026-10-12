<?php

use App\Models\Lead;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RbacSeeder::class);
});

function leadTestUserWithRole(string $roleSlug): User
{
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $role = Role::query()
        ->where('slug', $roleSlug)
        ->firstOrFail();

    $user->roles()->attach($role);

    return $user;
}

function leadTestCreate(array $attributes = []): Lead
{
    return Lead::create(array_merge([
        'name' => 'Test Lead',
        'email' => 'lead@example.com',
        'organization_name' => 'Test Organization',
        'phone' => '08012345678',
        'category' => 'Uniforms',
        'message' => 'Test enquiry',
        'source' => Lead::SOURCE_WEBSITE,
        'status' => Lead::STATUS_NEW,
    ], $attributes));
}

it('allows sales staff to create a lead and records ownership', function () {
    $user = leadTestUserWithRole('sales');

    $response = $this
        ->actingAs($user)
        ->post(route('leads.store'), [
            'name' => 'Sales Lead',
            'email' => 'sales@example.com',
            'organization_name' => 'Sales Organization',
            'phone' => '08011111111',
            'category' => 'Uniforms',
            'message' => 'Sales-created enquiry',
            'source' => Lead::SOURCE_WEBSITE,
        ]);

    $response->assertRedirect();

    $lead = Lead::query()->where('name', 'Sales Lead')->firstOrFail();

    expect($lead->created_by)->toBe($user->id);
    expect($lead->status)->toBe(Lead::STATUS_NEW);
});

it('allows sales staff to update their own lead', function () {
    $user = leadTestUserWithRole('sales');

    $lead = leadTestCreate([
        'created_by' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('leads.update', $lead), [
            'name' => 'Updated Lead',
            'email' => $lead->email,
            'organization_name' => $lead->organization_name,
            'phone' => $lead->phone,
            'category' => $lead->category,
            'message' => 'Updated message',
            'source' => Lead::SOURCE_WEBSITE,
            'status' => Lead::STATUS_CONTACTED,
        ]);

    $response->assertRedirect(route('leads.show', $lead));

    expect($lead->fresh()->name)->toBe('Updated Lead');
    expect($lead->fresh()->status)->toBe(Lead::STATUS_CONTACTED);
});

it('prevents sales staff from updating another users lead', function () {
    $owner = leadTestUserWithRole('sales');
    $salesUser = leadTestUserWithRole('sales');

    $lead = leadTestCreate([
        'created_by' => $owner->id,
    ]);

    $this
        ->actingAs($salesUser)
        ->put(route('leads.update', $lead), [
            'name' => 'Unauthorized Update',
            'email' => $lead->email,
            'organization_name' => $lead->organization_name,
            'phone' => $lead->phone,
            'category' => $lead->category,
            'message' => $lead->message,
            'source' => Lead::SOURCE_WEBSITE,
            'status' => Lead::STATUS_CONTACTED,
        ])
        ->assertForbidden();

    expect($lead->fresh()->name)->toBe('Test Lead');
});

it('allows administrators to update another users lead', function () {
    $owner = leadTestUserWithRole('sales');
    $admin = leadTestUserWithRole('admin');

    $lead = leadTestCreate([
        'created_by' => $owner->id,
    ]);

    $this
        ->actingAs($admin)
        ->put(route('leads.update', $lead), [
            'name' => 'Admin Updated Lead',
            'email' => $lead->email,
            'organization_name' => $lead->organization_name,
            'phone' => $lead->phone,
            'category' => $lead->category,
            'message' => $lead->message,
            'source' => Lead::SOURCE_WEBSITE,
            'status' => Lead::STATUS_CONTACTED,
        ])
        ->assertRedirect(route('leads.show', $lead));

    expect($lead->fresh()->name)->toBe('Admin Updated Lead');
});

it('allows sales staff to convert their own lead', function () {
    $user = leadTestUserWithRole('sales');

    $lead = leadTestCreate([
        'created_by' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('leads.convert', $lead), [
            'name' => 'Converted Organization',
            'type' => 'school',
            'ownership' => 'private',
            'phone' => $lead->phone,
            'email' => $lead->email,
            'state' => 'Abia',
        ]);

    $response->assertRedirect();

    $lead = $lead->fresh();

    expect($lead->organization_id)->not->toBeNull();
    expect($lead->status)->toBe(Lead::STATUS_CONVERTED);
    expect($lead->converted_at)->not->toBeNull();

    $organization = Organization::findOrFail($lead->organization_id);

    expect($organization->name)->toBe('Converted Organization');
    expect($organization->organization_code)->toBe(
        sprintf('ORG-%06d', $organization->id)
    );
});

it('prevents sales staff from converting another users lead', function () {
    $owner = leadTestUserWithRole('sales');
    $salesUser = leadTestUserWithRole('sales');

    $lead = leadTestCreate([
        'created_by' => $owner->id,
    ]);

    $this
        ->actingAs($salesUser)
        ->post(route('leads.convert', $lead), [
            'name' => 'Unauthorized Organization',
            'type' => 'school',
            'ownership' => 'private',
        ])
        ->assertForbidden();

    expect($lead->fresh()->organization_id)->toBeNull();
});

it('allows administrators to convert another users lead', function () {
    $owner = leadTestUserWithRole('sales');
    $admin = leadTestUserWithRole('admin');

    $lead = leadTestCreate([
        'created_by' => $owner->id,
    ]);

    $this
        ->actingAs($admin)
        ->post(route('leads.convert', $lead), [
            'name' => 'Admin Converted Organization',
            'type' => 'company',
            'ownership' => 'private',
        ])
        ->assertRedirect();

    expect($lead->fresh()->organization_id)->not->toBeNull();
});

it('allows administrators to delete leads', function () {
    $owner = leadTestUserWithRole('sales');
    $admin = leadTestUserWithRole('admin');

    $lead = leadTestCreate([
        'created_by' => $owner->id,
    ]);

    $this
        ->actingAs($admin)
        ->delete(route('leads.destroy', $lead))
        ->assertRedirect(route('leads.index'));

    expect(Lead::find($lead->id))->toBeNull();
});

it('prevents sales staff from deleting leads', function () {
    $user = leadTestUserWithRole('sales');

    $lead = leadTestCreate([
        'created_by' => $user->id,
    ]);

    $this
        ->actingAs($user)
        ->delete(route('leads.destroy', $lead))
        ->assertForbidden();

    expect(Lead::find($lead->id))->not->toBeNull();
});
