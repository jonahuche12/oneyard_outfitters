<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class FollowUpRbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'View Follow-ups',
                'slug' => 'follow-ups.view',
                'description' => 'View organization follow-up records.',
            ],
            [
                'name' => 'Create Follow-ups',
                'slug' => 'follow-ups.create',
                'description' => 'Create organization follow-up records.',
            ],
            [
                'name' => 'Update Follow-ups',
                'slug' => 'follow-ups.update',
                'description' => 'Update organization follow-up records.',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData
            );
        }

        $roles = [
            'super-admin' => [
                'follow-ups.view',
                'follow-ups.create',
                'follow-ups.update',
            ],

            'admin' => [
                'follow-ups.view',
                'follow-ups.create',
                'follow-ups.update',
            ],

            'sales' => [
                'follow-ups.view',
                'follow-ups.create',
                'follow-ups.update',
            ],

            'procurement' => [
                'follow-ups.view',
            ],

            'production' => [
                'follow-ups.view',
            ],

            'quality-control' => [
                'follow-ups.view',
            ],

            'finance' => [
                'follow-ups.view',
            ],

            'delivery' => [
                'follow-ups.view',
            ],
        ];

        foreach ($roles as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn(
                'slug',
                $permissionSlugs
            )->pluck('id');

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}