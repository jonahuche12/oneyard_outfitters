<?php

namespace Tests\Feature\Stores;

use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('stores'));
        $this->assertTrue(Schema::hasTable('store_staff'));

        $this->assertTrue(Schema::hasColumns('stores', [
            'id', 'name', 'code', 'type', 'general_warehouse_slot', 'address',
            'phone', 'is_active', 'created_by', 'created_at', 'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('store_staff', [
            'id', 'store_id', 'user_id', 'role', 'starts_at', 'ends_at',
            'ended_reason', 'assigned_by', 'ended_by', 'active_user_slot',
            'coordinator_slot', 'created_at', 'updated_at',
        ]));
    }

    public function test_required_unique_indexes_exist(): void
    {
        $this->assertUniqueIndex('stores', ['name']);
        $this->assertUniqueIndex('stores', ['code']);
        $this->assertUniqueIndex('stores', ['general_warehouse_slot']);
        $this->assertUniqueIndex('store_staff', ['user_id', 'active_user_slot']);
        $this->assertUniqueIndex('store_staff', ['store_id', 'coordinator_slot']);
    }

    public function test_required_foreign_keys_exist(): void
    {
        $this->assertForeignKey('stores', 'created_by', 'users');
        $this->assertForeignKey('store_staff', 'store_id', 'stores');
        $this->assertForeignKey('store_staff', 'user_id', 'users');
        $this->assertForeignKey('store_staff', 'assigned_by', 'users');
        $this->assertForeignKey('store_staff', 'ended_by', 'users');
    }

    public function test_store_name_must_be_unique(): void
    {
        $this->makeStore(['name' => 'Ikeja Fabrics']);

        $this->expectException(QueryException::class);

        $this->makeStore(['name' => 'Ikeja Fabrics']);
    }

    public function test_store_code_must_be_unique(): void
    {
        $this->makeStore(['code' => 'IKJ01']);

        $this->expectException(QueryException::class);

        $this->makeStore(['code' => 'IKJ01']);
    }

    public function test_only_one_warehouse_can_exist(): void
    {
        $this->makeStore(['type' => Store::TYPE_WAREHOUSE]);

        $this->expectException(QueryException::class);

        $this->makeStore(['type' => Store::TYPE_WAREHOUSE]);
    }

    public function test_a_store_cannot_have_two_active_coordinators(): void
    {
        $store = $this->makeStore();
        $this->assign($store, User::factory()->create(), StoreStaff::ROLE_COORDINATOR);

        $this->expectException(QueryException::class);

        $this->assign($store, User::factory()->create(), StoreStaff::ROLE_COORDINATOR);
    }

    public function test_a_user_cannot_have_two_active_store_assignments(): void
    {
        $user = User::factory()->create();
        $this->assign($this->makeStore(), $user, StoreStaff::ROLE_ASSISTANT);

        $this->expectException(QueryException::class);

        $this->assign($this->makeStore(), $user, StoreStaff::ROLE_ASSISTANT);
    }

    public function test_ended_assignments_do_not_block_a_replacement_coordinator(): void
    {
        $store = $this->makeStore();

        $first = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_COORDINATOR);
        $first->update([
            'ends_at' => now(),
            'ended_reason' => 'Replaced by a new coordinator',
        ]);

        $second = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_COORDINATOR);

        $this->assertSame(2, StoreStaff::query()->where('store_id', $store->id)->count());
        $this->assertNull($first->fresh()->coordinator_slot);
        $this->assertEquals(1, $second->fresh()->coordinator_slot);
    }

    public function test_a_store_can_have_many_active_assistants(): void
    {
        $store = $this->makeStore();

        foreach (range(1, 3) as $ignored) {
            $this->assign($store, User::factory()->create(), StoreStaff::ROLE_ASSISTANT);
        }

        $this->assertSame(3, StoreStaff::query()->where('store_id', $store->id)->active()->count());
    }

    public function test_optional_columns_are_nullable(): void
    {
        $store = $this->makeStore();
        $assignment = $this->assign($store, User::factory()->create(), StoreStaff::ROLE_ASSISTANT)->fresh();

        $this->assertNull($store->fresh()->address);
        $this->assertNull($store->fresh()->phone);
        $this->assertNull($store->fresh()->created_by);
        $this->assertNull($assignment->ends_at);
        $this->assertNull($assignment->ended_reason);
        $this->assertNull($assignment->assigned_by);
        $this->assertNull($assignment->ended_by);
    }

    public function test_a_user_with_assignment_history_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $this->assign($this->makeStore(), $user, StoreStaff::ROLE_ASSISTANT);

        $this->expectException(QueryException::class);

        $user->delete();
    }

    private function makeStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'name' => 'Store '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ], $attributes));
    }

    private function assign(Store $store, User $user, string $role, array $attributes = []): StoreStaff
    {
        return StoreStaff::query()->create(array_merge([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
            'starts_at' => now(),
        ], $attributes));
    }

    private function assertUniqueIndex(string $table, array $columns): void
    {
        $found = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === $columns);

        $this->assertTrue($found, "Missing unique index on {$table} (".implode(', ', $columns).')');
    }

    private function assertForeignKey(string $table, string $column, string $foreignTable): void
    {
        $found = collect(Schema::getForeignKeys($table))
            ->contains(fn (array $key) => $key['columns'] === [$column] && $key['foreign_table'] === $foreignTable);

        $this->assertTrue($found, "Missing foreign key {$table}.{$column} -> {$foreignTable}");
    }
}
