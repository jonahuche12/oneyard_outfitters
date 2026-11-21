<?php

namespace Tests\Feature\Stock;

use App\Models\StockItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockItemIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_name_and_random_code_are_generated_on_creation(): void
    {
        $item = $this->makeItem();

        $this->assertSame('ONYD-STK-MENSWEAR-PREMIUMCO', $item->name);
        $this->assertMatchesRegularExpression('/^STK-[0-9]{6}$/', $item->code);
    }

    public function test_generated_names_receive_a_suffix_when_duplicated(): void
    {
        $first = $this->makeItem();
        $second = $this->makeItem();

        $this->assertSame('ONYD-STK-MENSWEAR-PREMIUMCO', $first->name);
        $this->assertSame('ONYD-STK-MENSWEAR-PREMIUMCO-2', $second->name);
        $this->assertNotSame($first->code, $second->code);
    }

    public function test_blank_category_uses_general(): void
    {
        $item = $this->makeItem([
            'category' => null,
            'description' => 'Blue Water Bottle',
        ]);

        $this->assertSame('ONYD-STK-GENERAL-BLUEWATER', $item->name);
    }

    public function test_name_regenerates_when_category_or_description_changes(): void
    {
        $item = $this->makeItem();

        $item->update([
            'category' => 'School Wear',
            'description' => 'Blue School Trousers',
        ]);

        $this->assertSame('ONYD-STK-SCHOOLWEAR-BLUESCHOO', $item->fresh()->name);
    }

    public function test_stock_code_is_immutable_after_creation(): void
    {
        $item = $this->makeItem();
        $originalCode = $item->code;

        $item->code = 'STK-123456';
        $item->save();

        $this->assertSame($originalCode, $item->fresh()->code);
    }

    public function test_stock_codes_are_unique_across_created_items(): void
    {
        $items = collect();

        for ($i = 0; $i < 20; $i++) {
            $items->push($this->makeItem());
        }

        $codes = $items->pluck('code');

        $this->assertCount(20, $codes->unique());
        $this->assertTrue($codes->every(
            fn (string $code): bool => preg_match('/^STK-[0-9]{6}$/', $code) === 1
        ));
    }

    private function makeItem(array $overrides = []): StockItem
    {
        $user = User::factory()->create();

        $store = Store::query()->create([
            'name' => 'Identity Test '.Str::random(8),
            'code' => strtoupper(Str::random(8)),
        ]);

        return StockItem::query()->create(array_merge([
            'store_id' => $store->id,
            'created_by' => $user->id,
            'description' => 'Premium Cotton Shirt',
            'category' => "Men's Wear",
            'unit' => StockItem::UNIT_YARD,
            'unit_cost' => '100.000000',
        ], $overrides));
    }
}
