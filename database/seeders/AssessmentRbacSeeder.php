<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssessmentRbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'View Assessments',
                'slug' => 'assessments.view',
                'description' => 'View organization assessments.',
            ],
            [
                'name' => 'Create Assessments',
                'slug' => 'assessments.create',
                'description' => 'Create organization assessments.',
            ],
            [
                'name' => 'Update Assessments',
                'slug' => 'assessments.update',
                'description' => 'Update organization assessments.',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData
            );
        }

        $permissionSlugs = collect($permissions)
            ->pluck('slug')
            ->all();

        $roles = [
            'super-admin' => $permissionSlugs,

            'admin' => [
                'assessments.view',
                'assessments.create',
                'assessments.update',
            ],

            'sales' => [
                'assessments.view',
                'assessments.create',
                'assessments.update',
            ],

            'procurement' => [
                'assessments.view',
            ],

            'production' => [
                'assessments.view',
            ],

            'quality-control' => [
                'assessments.view',
            ],

            'finance' => [
                'assessments.view',
            ],

            'delivery' => [
                'assessments.view',
            ],
        ];

        foreach ($roles as $roleSlug => $rolePermissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn(
                'slug',
                $rolePermissionSlugs
            )->pluck('id');

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}