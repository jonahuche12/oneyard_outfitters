<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('SUPER_ADMIN_NAME=Super Administrator');
        putenv('SUPER_ADMIN_EMAIL=admin@oneyard.test');
        putenv('SUPER_ADMIN_PASSWORD=TestPassword123!');
    }

    public function test_super_admin_seeder_creates_the_super_admin_account(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()
            ->where('email', 'admin@oneyard.test')
            ->firstOrFail();

        $this->assertSame(
            'Super Administrator',
            $user->name
        );

        $this->assertTrue($user->is_active);

        $this->assertTrue(
            Hash::check(
                'ChangeThisDevelopmentPassword123!',
                $user->password
            )
        );

        $this->assertTrue(
            $user->roles()
                ->where('slug', 'super-admin')
                ->exists()
        );
    }

    public function test_super_admin_seeder_is_idempotent_and_preserves_existing_password(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()
            ->where('email', 'admin@oneyard.test')
            ->firstOrFail();

        $originalPasswordHash = $user->password;

        $user->update([
            'is_active' => false,
            'password' => 'ChangeThisDevelopmentPassword123!',
        ]);

        $this->seed(SuperAdminSeeder::class);

        $user->refresh();

        $this->assertTrue($user->is_active);

        $this->assertNotSame(
            $originalPasswordHash,
            $user->password
        );

        $this->assertTrue(
            Hash::check(
                'ChangeThisDevelopmentPassword123!',
                $user->password
            )
        );

        $this->assertTrue(
            $user->roles()
                ->where('slug', 'super-admin')
                ->exists()
        );
    }
}