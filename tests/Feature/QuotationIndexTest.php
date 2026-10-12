<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QuotationIndexTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private function viewer(): User
    {
        $role = Role::create([
            'name' => 'Quotation Viewer',
            'slug' => 'quotation-viewer',
            'description' => 'Test role.',
        ]);

        $role->permissions()->attach(
            Permission::firstOrCreate(
                ['slug' => 'quotations.view'],
                ['name' => 'Quotations View', 'description' => 'Test permission.']
            )
        );

        $user = User::factory()->create(['is_active' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    private function quotation(
        Organization $organization,
        string $status = Quotation::STATUS_DRAFT,
        ?string $number = null
    ): Quotation {
        $this->sequence++;

        return Quotation::create([
            'quotation_number' => $number ?? sprintf('QUO-T%05d', $this->sequence),
            'organization_id' => $organization->id,
            'created_by' => User::factory()->create()->id,
            'quotation_date' => now()->toDateString(),
            'expected_delivery_days' => 30,
            'status' => $status,
            'subtotal' => 0,
            'discount' => 0,
            'additional_charges' => 0,
            'total' => 0,
        ]);
    }

    private function ajax(User $user, array $query = []): TestResponse
    {
        return $this->actingAs($user)->get(
            route('quotations.index', $query),
            ['X-Requested-With' => 'XMLHttpRequest']
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('quotations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('quotations.index'))
            ->assertForbidden();
    }

    public function test_index_renders_the_full_page_with_filters_and_results(): void
    {
        $this->quotation(
            Organization::factory()->create(['name' => 'Alpha School']),
            number: 'QUO-AAA001'
        );

        $this->actingAs($this->viewer())
            ->get(route('quotations.index'))
            ->assertOk()
            ->assertViewIs('quotations.index')
            ->assertSee('Quotation Records')
            ->assertSee('quotations-filter-form', false)
            ->assertSee('quotations-results', false)
            ->assertSee('QUO-AAA001')
            ->assertSee('Alpha School');
    }

    public function test_ajax_request_returns_only_the_results_partial(): void
    {
        $this->quotation(
            Organization::factory()->create(),
            number: 'QUO-AAA001'
        );

        $response = $this->ajax($this->viewer())
            ->assertOk()
            ->assertViewIs('quotations.partials.table')
            ->assertSee('data-list-total="1"', false)
            ->assertSee('QUO-AAA001');

        $this->assertStringNotContainsString('<html', $response->getContent());
    }

    public function test_search_matches_quotation_number_organization_name_and_code(): void
    {
        $alpha = Organization::factory()->create(['name' => 'Alpha School']);
        $beta = Organization::factory()->create(['name' => 'Beta Academy']);

        $this->quotation($alpha, number: 'QUO-AAA001');
        $this->quotation($beta, number: 'QUO-BBB001');

        $viewer = $this->viewer();

        $this->ajax($viewer, ['search' => 'AAA001'])
            ->assertSee('QUO-AAA001')
            ->assertDontSee('QUO-BBB001');

        $this->ajax($viewer, ['search' => 'Beta Acad'])
            ->assertSee('QUO-BBB001')
            ->assertDontSee('QUO-AAA001');

        $this->ajax($viewer, ['search' => $alpha->organization_code])
            ->assertSee('QUO-AAA001')
            ->assertDontSee('QUO-BBB001');
    }

    public function test_status_filter_limits_results(): void
    {
        $organization = Organization::factory()->create();

        $this->quotation($organization, Quotation::STATUS_DRAFT, 'QUO-DRAFT1');
        $this->quotation($organization, Quotation::STATUS_ACCEPTED, 'QUO-ACCEPT1');

        $this->ajax($this->viewer(), ['status' => Quotation::STATUS_ACCEPTED])
            ->assertSee('QUO-ACCEPT1')
            ->assertDontSee('QUO-DRAFT1');
    }

    public function test_unknown_status_is_ignored(): void
    {
        $organization = Organization::factory()->create();

        $this->quotation($organization, Quotation::STATUS_DRAFT, 'QUO-DRAFT1');
        $this->quotation($organization, Quotation::STATUS_ACCEPTED, 'QUO-ACCEPT1');

        $this->ajax($this->viewer(), ['status' => 'bogus'])
            ->assertOk()
            ->assertSee('QUO-DRAFT1')
            ->assertSee('QUO-ACCEPT1');
    }

    public function test_results_are_paginated_fifteen_per_page_and_keep_filters(): void
    {
        $alpha = Organization::factory()->create(['name' => 'Alpha School']);
        $beta = Organization::factory()->create(['name' => 'Beta Academy']);

        for ($i = 0; $i < 16; $i++) {
            $this->quotation($alpha);
        }

        $this->quotation($beta);

        $viewer = $this->viewer();

        $first = $this->ajax($viewer, ['search' => 'Alpha'])->assertOk();

        $this->assertSame(15, substr_count($first->getContent(), 'data-quotation-row='));
        $this->assertStringContainsString('data-list-total="16"', $first->getContent());
        $this->assertStringContainsString('search=Alpha', $first->getContent());

        $second = $this->ajax($viewer, ['search' => 'Alpha', 'page' => 2])->assertOk();

        $this->assertSame(1, substr_count($second->getContent(), 'data-quotation-row='));
    }

    public function test_empty_state_explains_how_to_create_a_quotation(): void
    {
        $this->ajax($this->viewer())
            ->assertOk()
            ->assertSee('No quotations found.')
            ->assertSee('choose Create Quotation');
    }

    public function test_empty_search_result_offers_to_clear_the_filters(): void
    {
        $this->quotation(Organization::factory()->create(), number: 'QUO-AAA001');

        $this->ajax($this->viewer(), ['search' => 'nothing-matches-this'])
            ->assertOk()
            ->assertSee('No quotations found.')
            ->assertSee('Clear search');
    }
}
