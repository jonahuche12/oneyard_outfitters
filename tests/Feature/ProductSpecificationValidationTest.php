<?php

namespace Tests\Feature;

use App\Actions\ProductSpecifications\CreateProductSpecification;
use App\Http\Requests\StoreProductSpecificationRequest;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\ProductSpecification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProductSpecificationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RbacSeeder::class);
    }

    public function test_user_with_create_permission_can_create_specification(): void
    {
        $user = $this->userWithRole('sales');

        $organization = Organization::factory()->create();

        $specification = app(CreateProductSpecification::class)->execute(
            $user,
            [
                'organization_id' => $organization->id,
                'specification_date' => '2026-09-21',
                'item_name' => 'White School Shirt',
                'product_type' => 'uniform',
                'description' => 'White short-sleeve school shirt.',
                'unit' => 'piece',
                'unit_price' => 12500,
            ]
        );

        $this->assertInstanceOf(
            ProductSpecification::class,
            $specification
        );

        $this->assertSame($user->id, $specification->created_by);

        $this->assertSame(
            $organization->id,
            $specification->organization_id
        );
    }

    public function test_create_action_sets_authenticated_user_as_creator(): void
    {
        $user = $this->userWithRole('sales');

        $organization = Organization::factory()->create();

        $specification = app(CreateProductSpecification::class)->execute(
            $user,
            [
                'organization_id' => $organization->id,
                'specification_date' => '2026-09-21',
                'item_name' => 'School Trousers',
                'product_type' => 'uniform',
                'description' => 'Green school trousers.',
                'unit' => 'piece',
                'unit_price' => 10000,

                // This must be ignored by the action.
                'created_by' => 999999,
            ]
        );

        $this->assertSame(
            $user->id,
            $specification->created_by
        );
    }

    public function test_contact_must_belong_to_selected_organization(): void
    {
        $organizationA = Organization::factory()->create();

        $organizationB = Organization::factory()->create();

        $contact = Contact::factory()->create([
            'organization_id' => $organizationB->id,
        ]);

        $data = [
            'organization_id' => $organizationA->id,
            'contact_id' => $contact->id,
            'specification_date' => '2026-09-21',
            'item_name' => 'School Shirt',
            'product_type' => 'uniform',
            'description' => 'White school shirt.',
            'unit' => 'piece',
            'unit_price' => 10000,
        ];

        $validator = $this->validatorFor($data);

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('contact_id')
        );
    }

    public function test_unit_price_cannot_be_negative(): void
    {
        $organization = Organization::factory()->create();

        $data = [
            'organization_id' => $organization->id,
            'specification_date' => '2026-09-21',
            'item_name' => 'School Shirt',
            'product_type' => 'uniform',
            'description' => 'White school shirt.',
            'unit' => 'piece',
            'unit_price' => -100,
        ];

        $validator = $this->validatorFor($data);

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('unit_price')
        );
    }

    public function test_invalid_product_type_is_rejected(): void
    {
        $organization = Organization::factory()->create();

        $data = [
            'organization_id' => $organization->id,
            'specification_date' => '2026-09-21',
            'item_name' => 'School Shirt',
            'product_type' => 'invalid_type',
            'description' => 'White school shirt.',
            'unit' => 'piece',
            'unit_price' => 10000,
        ];

        $validator = $this->validatorFor($data);

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('product_type')
        );
    }

    public function test_invalid_status_is_rejected(): void
    {
        $organization = Organization::factory()->create();

        $data = [
            'organization_id' => $organization->id,
            'specification_date' => '2026-09-21',
            'item_name' => 'School Shirt',
            'product_type' => 'uniform',
            'description' => 'White school shirt.',
            'unit' => 'piece',
            'unit_price' => 10000,
            'status' => 'approved',
        ];

        $validator = $this->validatorFor($data);

        $this->assertTrue($validator->fails());

        $this->assertTrue(
            $validator->errors()->has('status')
        );
    }

    public function test_required_fields_are_validated(): void
    {
        $validator = $this->validatorFor([]);

        $this->assertTrue($validator->fails());

        foreach ([
            'organization_id',
            'specification_date',
            'item_name',
            'product_type',
            'description',
            'unit',
            'unit_price',
        ] as $field) {
            $this->assertTrue(
                $validator->errors()->has($field),
                "{$field} should be required"
            );
        }
    }

    public function test_zero_unit_price_is_allowed(): void
    {
        $organization = Organization::factory()->create();

        $data = [
            'organization_id' => $organization->id,
            'specification_date' => '2026-09-21',
            'item_name' => 'Sample Product',
            'product_type' => 'other',
            'description' => 'Sample product specification.',
            'unit' => 'piece',
            'unit_price' => 0,
        ];

        $validator = $this->validatorFor($data);

        $this->assertFalse($validator->fails());
    }

    /**
     * Build a validator from the actual StoreProductSpecificationRequest
     * without triggering FormRequest authorization.
     */
    private function validatorFor(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = StoreProductSpecificationRequest::create(
            '/',
            'POST',
            $data
        );

        return Validator::make(
            $data,
            $request->rules()
        );
    }

    private function userWithRole(string $roleSlug): User
    {
        $user = User::factory()->create();

        $role = Role::query()
            ->where('slug', $roleSlug)
            ->firstOrFail();

        $user->roles()->attach($role);

        return $user;
    }
}