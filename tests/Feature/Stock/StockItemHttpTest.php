<?php

namespace Tests\Feature\Stock;

use App\Models\Role;
use App\Models\StockItem;
use App\Models\StockItemPhoto;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockItemHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_can_view_only_stock_owned_by_their_assigned_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $assignedStore = $this->makeStore();
        $otherStore = $this->makeStore();

        $this->assign($coordinator, $assignedStore, StoreStaff::ROLE_COORDINATOR);

        $this->makeItem($assignedStore, $coordinator, 'Assigned store fabric');
        $this->makeItem($otherStore, $coordinator, 'Other store fabric');

        $this->actingAs($coordinator)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Assigned store fabric')
            ->assertDontSee('Other store fabric');

        $this->actingAs($coordinator)
            ->get(route('stock.index', ['store_id' => $otherStore->id]))
            ->assertOk()
            ->assertSee('Assigned store fabric')
            ->assertDontSee('Other store fabric');
    }

    public function test_assistant_can_view_stock_but_cannot_register_stock_items(): void
    {
        $assistant = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();

        $this->assign($assistant, $store, StoreStaff::ROLE_ASSISTANT);
        $this->makeItem($store, $assistant, 'Assistant visible item');

        $this->actingAs($assistant)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Assistant visible item');

        $this->actingAs($assistant)
            ->get(route('stock.items.create'))
            ->assertForbidden();

        $this->actingAs($assistant)
            ->post(route('stock.items.store'), [
                'description' => 'Assistant attempted item',
                'unit' => StockItem::UNIT_YARD,
                'quantity' => '10',
                'unit_cost' => '50',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('stock_items', [
            'description' => 'Assistant attempted item',
        ]);
    }

    public function test_coordinator_registers_stock_for_assigned_store_with_opening_balance_and_photos(): void
    {
        Storage::fake('local');

        $coordinator = User::factory()->create(['is_active' => true]);
        $assignedStore = $this->makeStore();
        $otherStore = $this->makeStore();

        $this->assign($coordinator, $assignedStore, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->get(route('stock.items.create'))
            ->assertOk()
            ->assertSee($assignedStore->name);

        $this->actingAs($coordinator)
            ->post(route('stock.items.store'), [
                'store_id' => $otherStore->id,
                'description' => 'Coordinator-owned fabric',
                'category' => 'Fabric',
                'unit' => StockItem::UNIT_YARD,
                'quantity' => '20.500',
                'unit_cost' => '80.00',
                'suggested_price' => '120.00',
                'photos' => [
                    UploadedFile::fake()->image('fabric.jpg'),
                ],
            ])
            ->assertRedirect(route('stores.show', $assignedStore))
            ->assertSessionHas('success');

        $item = StockItem::query()
            ->where('description', 'Coordinator-owned fabric')
            ->firstOrFail();

        $this->assertSame($assignedStore->id, $item->store_id);
        $this->assertSame($coordinator->id, $item->created_by);
        $this->assertNull($item->intake_id);

        $this->assertDatabaseMissing('stock_items', [
            'store_id' => $otherStore->id,
            'description' => 'Coordinator-owned fabric',
        ]);

        $this->assertDatabaseHas('stock_levels', [
            'stock_item_id' => $item->id,
            'store_id' => $assignedStore->id,
            'quantity' => '20.500',
            'average_unit_cost' => '80.000000',
            'inventory_value' => '1640.00',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'store_id' => $assignedStore->id,
            'type' => StockMovement::TYPE_OPENING_STOCK,
            'quantity' => '20.500',
            'movement_value' => '1640.00',
        ]);

        $this->assertDatabaseHas('stock_item_photos', [
            'stock_item_id' => $item->id,
            'uploaded_by' => $coordinator->id,
            'original_name' => 'fabric.jpg',
        ]);

        $photo = $item->photos()->firstOrFail();
        Storage::disk('local')->assertExists($photo->file_path);

        $this->assertDatabaseCount('intakes', 0);
    }

    public function test_coordinator_can_register_stock_with_ten_photos(): void
    {
        Storage::fake('local');

        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->post(route('stock.items.store'), [
                'description' => 'Stock with ten photos',
                'unit' => StockItem::UNIT_YARD,
                'quantity' => '10',
                'unit_cost' => '50',
                'photos' => $this->fakePhotos(10, 'registration'),
            ])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $item = StockItem::query()
            ->where('description', 'Stock with ten photos')
            ->firstOrFail();

        $this->assertSame(10, $item->photos()->count());
        foreach ($item->photos as $photo) {
            Storage::disk('local')->assertExists($photo->file_path);
        }
    }

    public function test_coordinator_cannot_register_stock_with_eleven_photos(): void
    {
        Storage::fake('local');

        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);

        $this->actingAs($coordinator)
            ->from(route('stock.items.create'))
            ->post(route('stock.items.store'), [
                'description' => 'Stock with eleven photos',
                'unit' => StockItem::UNIT_YARD,
                'quantity' => '10',
                'unit_cost' => '50',
                'photos' => $this->fakePhotos(11, 'too-many'),
            ])
            ->assertRedirect(route('stock.items.create'))
            ->assertSessionHasErrors('photos');

        $this->assertDatabaseMissing('stock_items', [
            'description' => 'Stock with eleven photos',
        ]);
    }

    public function test_coordinator_can_add_photos_up_to_ten_when_editing_stock(): void
    {
        Storage::fake('local');

        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($store, $coordinator, 'Eight existing photos');

        $this->attachExistingPhotos($item, $coordinator, 8);

        $this->actingAs($coordinator)
            ->put(route('stock.items.update', $item), [
                'description' => 'Eight existing photos',
                'photos' => $this->fakePhotos(2, 'edit-allowed'),
            ])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $this->assertSame(10, $item->photos()->count());
    }

    public function test_coordinator_cannot_exceed_ten_photos_when_editing_stock(): void
    {
        Storage::fake('local');

        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($store, $coordinator, 'Nine existing photos');

        $this->attachExistingPhotos($item, $coordinator, 9);

        $this->actingAs($coordinator)
            ->from(route('stock.items.edit', $item))
            ->put(route('stock.items.update', $item), [
                'description' => 'Should not be saved',
                'photos' => $this->fakePhotos(2, 'edit-rejected'),
            ])
            ->assertRedirect(route('stock.items.edit', $item))
            ->assertSessionHasErrors('photos');

        $this->assertSame(9, $item->photos()->count());

        $this->assertDatabaseHas('stock_items', [
            'id' => $item->id,
            'description' => 'Nine existing photos',
        ]);
    }

    public function test_coordinator_can_edit_item_details_from_their_store_stock_list(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($coordinator, $store, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($store, $coordinator, 'Original item');

        $this->actingAs($coordinator)
            ->get(route('stock.index'))
            ->assertOk()
            ->assertSee(route('stock.items.edit', $item));

        $this->actingAs($coordinator)
            ->get(route('stock.items.edit', $item))
            ->assertOk()
            ->assertSee('Edit Stock Item')
            ->assertSee('Original item');

        $this->actingAs($coordinator)
            ->put(route('stock.items.update', $item), [
                'description' => 'Coordinator updated item',
                'category' => 'Fabric',
                'grade' => 'Premium',
                'suggested_price' => '200.00',
                'current_market_price' => '220.00',
            ])
            ->assertRedirect(route('stores.show', $store))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('stock_items', [
            'id' => $item->id,
            'store_id' => $store->id,
            'description' => 'Coordinator updated item',
            'category' => 'Fabric',
            'grade' => 'Premium',
            'suggested_price' => '200.00',
            'current_market_price' => '220.00',
        ]);
    }

    public function test_assistant_cannot_open_or_submit_stock_item_edits(): void
    {
        $assistant = User::factory()->create(['is_active' => true]);
        $store = $this->makeStore();
        $this->assign($assistant, $store, StoreStaff::ROLE_ASSISTANT);
        $item = $this->makeItem($store, $assistant, 'Assistant visible item');

        $this->actingAs($assistant)
            ->get(route('stock.items.edit', $item))
            ->assertForbidden();

        $this->actingAs($assistant)
            ->put(route('stock.items.update', $item), [
                'description' => 'Unauthorized edit',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('stock_items', [
            'id' => $item->id,
            'description' => 'Assistant visible item',
        ]);
    }

    public function test_coordinator_cannot_edit_stock_owned_by_another_store(): void
    {
        $coordinator = User::factory()->create(['is_active' => true]);
        $assignedStore = $this->makeStore();
        $otherStore = $this->makeStore();
        $this->assign($coordinator, $assignedStore, StoreStaff::ROLE_COORDINATOR);
        $item = $this->makeItem($otherStore, $coordinator, 'Other store item');

        $this->actingAs($coordinator)
            ->get(route('stock.items.edit', $item))
            ->assertForbidden();

        $this->actingAs($coordinator)
            ->put(route('stock.items.update', $item), [
                'description' => 'Unauthorized change',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('stock_items', [
            'id' => $item->id,
            'store_id' => $otherStore->id,
            'description' => 'Other store item',
        ]);
    }

    public function test_administrator_without_store_assignment_cannot_view_or_register_store_stock(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('stock.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('stock.items.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('stock.items.store'), [
                'description' => 'Admin attempted item',
                'unit' => StockItem::UNIT_YARD,
                'quantity' => '10',
                'unit_cost' => '50',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('stock_items', [
            'description' => 'Admin attempted item',
        ]);
    }

    public function test_unassigned_user_cannot_view_stock_or_open_creation_form(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('stock.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('stock.items.create'))
            ->assertForbidden();
    }

    private function fakePhotos(int $count, string $prefix): array
    {
        $photos = [];

        for ($index = 1; $index <= $count; $index++) {
            $photos[] = UploadedFile::fake()->image(
                $prefix.'-'.$index.'.jpg'
            );
        }

        return $photos;
    }

    private function attachExistingPhotos(
        StockItem $item,
        User $uploader,
        int $count
    ): void {
        for ($index = 1; $index <= $count; $index++) {
            $path = 'stock-items/'.$item->id.'/existing-'.$index.'.jpg';

            Storage::disk('local')->put($path, 'existing photo');

            StockItemPhoto::query()->create([
                'stock_item_id' => $item->id,
                'file_path' => $path,
                'original_name' => 'existing-'.$index.'.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 14,
                'uploaded_by' => $uploader->id,
            ]);
        }
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['is_active' => true]);

        $role = Role::query()->create([
            'name' => ucfirst($slug).' '.Str::random(6),
            'slug' => $slug,
            'description' => 'Stock HTTP test role',
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    private function makeStore(): Store
    {
        return Store::query()->create([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
            'type' => Store::TYPE_STORE,
            'is_active' => true,
        ]);
    }

    private function assign(User $user, Store $store, string $role): void
    {
        StoreStaff::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
            'assigned_by' => null,
        ]);
    }

    private function makeItem(Store $store, User $creator, string $description): StockItem
    {
        $item = StockItem::query()->create([
            'store_id' => $store->id,
            'description' => $description,
            'unit' => StockItem::UNIT_YARD,
            'approval_status' => StockItem::APPROVAL_PENDING,
            'created_by' => $creator->id,
        ]);

        StockLevel::query()->create([
            'stock_item_id' => $item->id,
            'store_id' => $store->id,
            'quantity' => 10,
            'quantity_quarantined' => 0,
            'average_unit_cost' => 50,
            'inventory_value' => 500,
            'quarantine_value' => 0,
        ]);

        return $item;
    }
}
