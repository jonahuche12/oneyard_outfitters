<?php

namespace Tests\Feature\Staff;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaffPhoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Bus::fake();
    }

    private function manager(): User
    {
        $role = Role::create([
            'name' => 'Staff Manager',
            'slug' => 'staff-manager',
            'description' => 'Test role.',
        ]);

        foreach (['users.view', 'users.create', 'users.update'] as $slug) {
            $role->permissions()->attach(
                Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $slug, 'description' => 'Test permission.']
                )
            );
        }

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Staff',
            'email' => 'new.staff@example.com',
            'phone' => '+2348012345678',
        ], $overrides);
    }

    public function test_staff_can_be_created_with_a_phone_number(): void
    {
        $this->actingAs($this->manager())
            ->post(route('staff.store'), $this->payload(['phone' => '+2348012345678']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new.staff@example.com',
            'phone' => '+2348012345678',
        ]);
    }

    public function test_staff_creation_requires_a_phone_number(): void
    {
        $payload = $this->payload();
        unset($payload['phone']);

        $this->actingAs($this->manager())
            ->post(route('staff.store'), $payload)
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', [
            'email' => 'new.staff@example.com',
        ]);
    }

    public function test_phone_formatting_characters_are_stripped(): void
    {
        $this->actingAs($this->manager())
            ->post(route('staff.store'), $this->payload(['phone' => '+234 801-234 5678']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new.staff@example.com',
            'phone' => '+2348012345678',
        ]);
    }

    public function test_phone_without_country_code_is_rejected(): void
    {
        $this->actingAs($this->manager())
            ->post(route('staff.store'), $this->payload(['phone' => '08012345678']))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'new.staff@example.com']);
    }

    public function test_phone_can_be_updated_and_cleared(): void
    {
        $manager = $this->manager();
        $staff = User::factory()->create(['is_active' => true, 'phone' => '+2348011111111']);

        $this->actingAs($manager)
            ->put(route('staff.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => '+2348022222222',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('+2348022222222', $staff->fresh()->phone);

        $this->actingAs($manager)
            ->put(route('staff.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($staff->fresh()->phone);
    }

    public function test_update_without_a_phone_field_keeps_the_existing_number(): void
    {
        $manager = $this->manager();
        $staff = User::factory()->create(['is_active' => true, 'phone' => '+2348011111111']);

        $this->actingAs($manager)
            ->put(route('staff.update', $staff), [
                'name' => 'Renamed Staff',
                'email' => $staff->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('+2348011111111', $staff->fresh()->phone);
        $this->assertSame('Renamed Staff', $staff->fresh()->name);
    }
}
