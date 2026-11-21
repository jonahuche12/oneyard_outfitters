<?php

namespace Tests\Feature\Stock;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StockItemReviewHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_item_review_routes_are_not_registered(): void
    {
        $this->assertFalse(Route::has('stock.items.review'));
        $this->assertFalse(Route::has('stock.items.review.store'));
    }
}
