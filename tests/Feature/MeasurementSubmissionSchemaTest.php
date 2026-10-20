<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeasurementSubmissionSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_measurement_tables_have_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('measurement_submissions'));
        $this->assertTrue(Schema::hasTable('individual_measurements'));
        $this->assertTrue(Schema::hasTable('measurement_size_quantities'));

        $this->assertTrue(Schema::hasColumns('measurement_submissions', [
            'id',
            'order_item_id',
            'revision',
            'measurement_method',
            'input_source',
            'status',
            'submitted_at',
            'reviewed_by',
            'reviewed_at',
            'correction_reason',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('individual_measurements', [
            'id',
            'measurement_submission_id',
            'person_label',
            'measurements',
            'notes',
            'position',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('measurement_size_quantities', [
            'id',
            'measurement_submission_id',
            'size_label',
            'quantity',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_measurement_tables_enforce_required_unique_constraints(): void
    {
        $this->assertUniqueIndex(
            'measurement_submissions',
            ['order_item_id', 'revision']
        );

        $this->assertUniqueIndex(
            'individual_measurements',
            ['measurement_submission_id', 'position']
        );

        $this->assertUniqueIndex(
            'measurement_size_quantities',
            ['measurement_submission_id', 'size_label']
        );
    }

    public function test_measurement_tables_have_required_foreign_keys(): void
    {
        $this->assertForeignKey(
            'measurement_submissions',
            'order_item_id',
            'order_items'
        );

        $this->assertForeignKey(
            'measurement_submissions',
            'reviewed_by',
            'users'
        );

        $this->assertForeignKey(
            'individual_measurements',
            'measurement_submission_id',
            'measurement_submissions'
        );

        $this->assertForeignKey(
            'measurement_size_quantities',
            'measurement_submission_id',
            'measurement_submissions'
        );
    }

    public function test_review_fields_and_optional_person_details_are_nullable(): void
    {
        foreach (['reviewed_by', 'reviewed_at', 'correction_reason', 'submitted_at'] as $column) {
            $this->assertTrue(
                Schema::getColumns('measurement_submissions')[
                    array_search(
                        $column,
                        array_column(Schema::getColumns('measurement_submissions'), 'name'),
                        true
                    )
                ]['nullable'],
                "Expected measurement_submissions.{$column} to be nullable."
            );
        }

        $individualColumns = Schema::getColumns('individual_measurements');

        foreach (['person_label', 'notes'] as $column) {
            $index = array_search(
                $column,
                array_column($individualColumns, 'name'),
                true
            );

            $this->assertNotFalse($index);
            $this->assertTrue(
                $individualColumns[$index]['nullable'],
                "Expected individual_measurements.{$column} to be nullable."
            );
        }
    }

    private function assertUniqueIndex(string $table, array $expectedColumns): void
    {
        $indexes = Schema::getIndexes($table);

        $matches = array_filter($indexes, function (array $index) use ($expectedColumns): bool {
            return ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === $expectedColumns;
        });

        $this->assertNotEmpty(
            $matches,
            "Expected a unique index on {$table} (" . implode(', ', $expectedColumns) . ').'
        );
    }

    private function assertForeignKey(
        string $table,
        string $column,
        string $referencedTable
    ): void {
        $foreignKeys = Schema::getForeignKeys($table);

        $matches = array_filter($foreignKeys, function (array $foreignKey) use (
            $column,
            $referencedTable
        ): bool {
            return ($foreignKey['columns'] ?? []) === [$column]
                && ($foreignKey['foreign_table'] ?? null) === $referencedTable;
        });

        $this->assertNotEmpty(
            $matches,
            "Expected {$table}.{$column} to reference {$referencedTable}."
        );
    }
}
