<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\StoreFundAssignment;
use App\Models\User;
use App\Policies\StoreFundAssignmentPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreFundAssignmentPolicyTest extends TestCase
{
    #[Test]
    public function finance_reviewer_can_view_and_review_store_requests_but_cannot_dispatch_or_manage_direct_assignments(): void
    {
        $user = $this->user(
            22,
            ['finance'],
            [
                StoreFundAssignmentPolicy::VIEW_PERMISSION,
                StoreFundAssignmentPolicy::REVIEW_PERMISSION,
            ]
        );

        $policy = new StoreFundAssignmentPolicy();

        $storeRequest = $this->assignment(
            StoreFundAssignment::SOURCE_STORE_REQUEST,
            30
        );

        $directAssignment = $this->assignment(
            StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
            30
        );

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->reviewRequests($user));
        $this->assertTrue($policy->view($user, $storeRequest));
        $this->assertFalse($policy->view($user, $directAssignment));

        $this->assertTrue($policy->approve($user, $storeRequest));
        $this->assertTrue($policy->reject($user, $storeRequest));
        $this->assertFalse($policy->dispatch($user, $storeRequest));

        $this->assertFalse($policy->approve($user, $directAssignment));
        $this->assertFalse($policy->reject($user, $directAssignment));
        $this->assertFalse($policy->dispatch($user, $directAssignment));
        $this->assertFalse($policy->createDirect(
            $user,
            new \App\Models\Store()
        ));
    }

    private function user(
        int $id,
        array $roles,
        array $permissions = [],
        bool $active = true
    ): User {
        $user = new class extends User {
            public array $testPermissions = [];

            public function hasPermission(string $slug): bool
            {
                return in_array($slug, $this->testPermissions, true);
            }
        };

        $user->setAttribute('id', $id);
        $user->setAttribute('is_active', $active);
        $user->testPermissions = $permissions;

        $roleModels = collect($roles)->map(function (string $slug) {
            $role = new Role();
            $role->setAttribute('slug', $slug);

            return $role;
        });

        $user->setRelation('roles', $roleModels);

        return $user;
    }

    private function assignment(
        string $source,
        int $requestedBy,
        ?int $dispatchedBy = null
    ): StoreFundAssignment {
        $assignment = new StoreFundAssignment();

        $assignment->setAttribute('source', $source);
        $assignment->setAttribute('requested_by', $requestedBy);
        $assignment->setAttribute('dispatched_by', $dispatchedBy);

        return $assignment;
    }

    #[Test]
    public function administrator_with_fund_permissions_can_view_management_queue(): void
    {
        $user = $this->user(
            10,
            ['admin'],
            [
                StoreFundAssignmentPolicy::VIEW_PERMISSION,
                StoreFundAssignmentPolicy::MANAGE_PERMISSION,
            ]
        );

        $this->assertTrue(
            (new StoreFundAssignmentPolicy())->viewAny($user)
        );
    }

    #[Test]
    public function finance_permission_for_remittances_does_not_grant_fund_management(): void
    {
        $user = $this->user(
            11,
            ['finance'],
            ['stores.remittances.review']
        );

        $policy = new StoreFundAssignmentPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse(
            $policy->approve(
                $user,
                $this->assignment(StoreFundAssignment::SOURCE_STORE_REQUEST, 20)
            )
        );
    }

    #[Test]
    public function inactive_administrator_cannot_manage_funds(): void
    {
        $user = $this->user(
            12,
            ['admin'],
            [
                StoreFundAssignmentPolicy::VIEW_PERMISSION,
                StoreFundAssignmentPolicy::MANAGE_PERMISSION,
            ],
            false
        );

        $policy = new StoreFundAssignmentPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse(
            $policy->approve(
                $user,
                $this->assignment(StoreFundAssignment::SOURCE_STORE_REQUEST, 20)
            )
        );
    }

    #[Test]
    public function administrator_can_handle_a_store_request(): void
    {
        $user = $this->user(
            13,
            ['admin'],
            [StoreFundAssignmentPolicy::MANAGE_PERMISSION]
        );

        $assignment = $this->assignment(
            StoreFundAssignment::SOURCE_STORE_REQUEST,
            20
        );

        $policy = new StoreFundAssignmentPolicy();

        $this->assertTrue($policy->approve($user, $assignment));
        $this->assertTrue($policy->reject($user, $assignment));
        $this->assertTrue($policy->dispatch($user, $assignment));
    }

    #[Test]
    public function administrator_can_handle_only_their_own_direct_assignment(): void
    {
        $user = $this->user(
            14,
            ['admin'],
            [StoreFundAssignmentPolicy::MANAGE_PERMISSION]
        );

        $policy = new StoreFundAssignmentPolicy();

        $ownAssignment = $this->assignment(
            StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
            14
        );

        $otherAdminAssignment = $this->assignment(
            StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
            15
        );

        $this->assertTrue($policy->approve($user, $ownAssignment));
        $this->assertTrue($policy->dispatch($user, $ownAssignment));

        $this->assertFalse($policy->approve($user, $otherAdminAssignment));
        $this->assertFalse($policy->reject($user, $otherAdminAssignment));
        $this->assertFalse($policy->dispatch($user, $otherAdminAssignment));
    }

    #[Test]
    public function super_admin_can_handle_another_administrators_direct_assignment(): void
    {
        $user = $this->user(
            16,
            ['super-admin'],
            [StoreFundAssignmentPolicy::MANAGE_PERMISSION]
        );

        $assignment = $this->assignment(
            StoreFundAssignment::SOURCE_DIRECT_ASSIGNMENT,
            17
        );

        $policy = new StoreFundAssignmentPolicy();

        $this->assertTrue($policy->approve($user, $assignment));
        $this->assertTrue($policy->reject($user, $assignment));
        $this->assertTrue($policy->dispatch($user, $assignment));
    }

    #[Test]
    public function dispatch_correction_is_limited_to_dispatcher_or_super_admin(): void
    {
        $policy = new StoreFundAssignmentPolicy();

        $dispatcher = $this->user(
            18,
            ['admin'],
            [StoreFundAssignmentPolicy::MANAGE_PERMISSION]
        );

        $otherAdmin = $this->user(
            19,
            ['admin'],
            [StoreFundAssignmentPolicy::MANAGE_PERMISSION]
        );

        $superAdmin = $this->user(
            20,
            ['super-admin'],
            [StoreFundAssignmentPolicy::MANAGE_PERMISSION]
        );

        $assignment = $this->assignment(
            StoreFundAssignment::SOURCE_STORE_REQUEST,
            21,
            18
        );

        $this->assertTrue($policy->correctDispatch($dispatcher, $assignment));
        $this->assertFalse($policy->correctDispatch($otherAdmin, $assignment));
        $this->assertTrue($policy->correctDispatch($superAdmin, $assignment));
    }
}
