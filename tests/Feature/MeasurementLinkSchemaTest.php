<?php

namespace Tests\Feature;

use App\Models\MeasurementLink;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeasurementLinkSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('measurement_links', [
            'id', 'order_id', 'contact_id', 'token_hash', 'expires_at', 'sent_at',
            'first_accessed_at', 'last_accessed_at', 'revoked_at', 'revoked_by',
            'revoke_reason', 'created_by', 'is_active_slot', 'created_at', 'updated_at',
        ]));
    }

    public function test_required_unique_indexes_exist(): void
    {
        $unique = collect(Schema::getIndexes('measurement_links'))
            ->where('unique', true)
            ->map(fn ($i) => implode(',', $i['columns']))
            ->all();

        $this->assertContains('token_hash', $unique);
        $this->assertContains('order_id,contact_id,is_active_slot', $unique);
    }

    public function test_required_foreign_keys_exist(): void
    {
        $fks = collect(Schema::getForeignKeys('measurement_links'))
            ->mapWithKeys(fn ($f) => [implode(',', $f['columns']) => $f['foreign_table']])
            ->all();

        $this->assertSame('orders', $fks['order_id'] ?? null);
        $this->assertSame('contacts', $fks['contact_id'] ?? null);
        $this->assertSame('users', $fks['revoked_by'] ?? null);
        $this->assertSame('users', $fks['created_by'] ?? null);
    }

    public function test_lifecycle_columns_are_nullable_and_token_is_hidden(): void
    {
        $nullable = collect(Schema::getColumns('measurement_links'))
            ->mapWithKeys(fn ($c) => [$c['name'] => $c['nullable']])
            ->all();

        foreach (['sent_at', 'first_accessed_at', 'last_accessed_at', 'revoked_at',
                  'revoked_by', 'revoke_reason', 'created_by', 'is_active_slot'] as $col) {
            $this->assertTrue($nullable[$col], "$col should be nullable");
        }
        foreach (['order_id', 'contact_id', 'token_hash', 'expires_at'] as $col) {
            $this->assertFalse($nullable[$col], "$col should be required");
        }

        $link = new MeasurementLink(['token_hash' => str_repeat('a', 64)]);
        $this->assertArrayNotHasKey('token_hash', $link->toArray());
    }

    public function test_order_exposes_measurement_links_relation(): void
    {
        $this->assertTrue(method_exists(Order::class, 'measurementLinks'));
    }
}
