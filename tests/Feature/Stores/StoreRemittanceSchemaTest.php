<?php

namespace Tests\Feature\Stores;

use App\Models\StoreRemittance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class StoreRemittanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('store_remittances', [
            'id', 'store_id', 'store_daily_cash_record_id', 'business_date',
            'amount_cash', 'amount_bank', 'method', 'reference', 'note',
            'status', 'resubmits_id', 'submitted_by', 'submitted_at',
            'reviewed_by', 'reviewed_at', 'review_reason',
            'created_at', 'updated_at',
        ]));
    }

    public function test_daily_record_link_is_a_composite_key_that_pins_the_store(): void
    {
        $composite = collect(Schema::getForeignKeys('store_remittances'))
            ->first(fn ($fk) => $fk['columns'] === ['store_daily_cash_record_id', 'store_id']);

        $this->assertNotNull($composite);
        $this->assertSame('store_daily_cash_records', $composite['foreign_table']);
        $this->assertSame(['id', 'store_id'], $composite['foreign_columns']);
    }

    public function test_expected_indexes_exist(): void
    {
        $names = collect(Schema::getIndexes('store_remittances'))->pluck('name');

        $this->assertTrue($names->contains('store_remittances_store_status_index'));
        $this->assertTrue($names->contains('store_remittances_daily_status_index'));
        $this->assertTrue($names->contains('store_remittances_store_date_index'));
    }

    public function test_config_defaults(): void
    {
        $this->assertSame(2, config('stores.remittance_flag_days'));
        $this->assertArrayHasKey('remittance_cutover_date', config('stores'));
    }

    public function test_total_amount_adds_cash_and_bank(): void
    {
        $remittance = new StoreRemittance(['amount_cash' => '1000.50', 'amount_bank' => '250.25']);

        $this->assertSame('1250.75', $remittance->totalAmount());
    }

    public function test_a_reviewed_remittance_cannot_change(): void
    {
        $remittance = $this->persistedStub(StoreRemittance::STATUS_CONFIRMED);
        $remittance->status = StoreRemittance::STATUS_REJECTED;

        $this->expectException(LogicException::class);
        $remittance->save();
    }

    public function test_submitted_fields_cannot_be_edited(): void
    {
        $remittance = $this->persistedStub(StoreRemittance::STATUS_PENDING);
        $remittance->amount_cash = '1.00';

        $this->expectException(LogicException::class);
        $remittance->save();
    }

    public function test_a_pending_row_cannot_be_set_to_an_unknown_status(): void
    {
        $remittance = $this->persistedStub(StoreRemittance::STATUS_PENDING);
        $remittance->status = 'cancelled';

        $this->expectException(LogicException::class);
        $remittance->save();
    }

    public function test_remittances_cannot_be_deleted(): void
    {
        $remittance = $this->persistedStub(StoreRemittance::STATUS_PENDING);

        $this->expectException(LogicException::class);
        $remittance->delete();
    }

    /**
     * An in-memory row that behaves as already saved. The model guards fire
     * before any query runs, so no related fixtures are needed for Step 1.
     */
    private function persistedStub(string $status): StoreRemittance
    {
        $remittance = new StoreRemittance();
        $remittance->forceFill([
            'id' => 1,
            'amount_cash' => '100.00',
            'amount_bank' => '0.00',
            'status' => $status,
        ]);
        $remittance->exists = true;
        $remittance->syncOriginal();

        return $remittance;
    }
}
