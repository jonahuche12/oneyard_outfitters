<?php

namespace Tests\Unit;

use App\Actions\Measurements\ValidateMeasurementPayload;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidateMeasurementPayloadTest extends TestCase
{
    private function shirt(array $override = []): array
    {
        return array_merge([
            'neck' => '15', 'chest' => '38', 'waist' => '32.5',
            'shoulder' => '17', 'sleeve' => '24', 'length' => '29',
        ], $override);
    }

    private function validate(?string $type, string $unit, $qty, array $input): array
    {
        return (new ValidateMeasurementPayload())->handle($type, $unit, $qty, $input);
    }

    private function assertRejects(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail('Expected validation failure for ' . $key);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    public function test_valid_individual_piece_payload_is_cleaned(): void
    {
        $out = $this->validate('shirt', 'piece', 2, ['method' => 'individual', 'people' => [
            ['label' => ' Ada ', 'measurements' => $this->shirt()],
            ['measurements' => $this->shirt(['waist' => '30'])],
        ]]);

        $this->assertCount(2, $out['people']);
        $this->assertSame('Ada', $out['people'][0]['label']);
        $this->assertSame('32.50', $out['people'][0]['measurements']['waist']);
        $this->assertSame(2, $out['people'][1]['position']);
    }

    public function test_piece_unit_requires_matching_person_count(): void
    {
        $this->assertRejects(fn () => $this->validate('shirt', 'piece', 3, ['method' => 'individual', 'people' => [
            ['measurements' => $this->shirt()],
        ]]), 'people');
    }

    public function test_pair_unit_accepts_any_person_count(): void
    {
        $out = $this->validate('trouser', 'pair', 10, ['method' => 'individual', 'people' => [[
            'measurements' => ['waist' => '30', 'hip' => '38', 'inseam' => '30', 'outseam' => '40', 'thigh' => '22', 'bottom' => '8'],
        ]]]);

        $this->assertCount(1, $out['people']);
    }

    public function test_bad_numbers_are_rejected(): void
    {
        foreach (['abc', '-5', '15.555', '', '1e2', '999', '2'] as $bad) {
            $this->assertRejects(fn () => $this->validate('shirt', 'pair', 1, ['method' => 'individual', 'people' => [
                ['measurements' => $this->shirt(['neck' => $bad])],
            ]]), 'people.0.neck');
        }
    }

    public function test_missing_field_is_rejected(): void
    {
        $m = $this->shirt();
        unset($m['sleeve']);
        $this->assertRejects(fn () => $this->validate('shirt', 'pair', 1, ['method' => 'individual', 'people' => [['measurements' => $m]]]), 'people.0.sleeve');
    }

    public function test_item_without_profile_cannot_use_individual_entry(): void
    {
        foreach (['other', null] as $type) {
            $this->assertRejects(fn () => $this->validate($type, 'piece', 1, ['method' => 'individual', 'people' => [['measurements' => []]]]), 'method');
        }
    }

    public function test_unknown_method_is_rejected(): void
    {
        $this->assertRejects(fn () => $this->validate('shirt', 'piece', 1, ['method' => 'excel']), 'method');
        $this->assertRejects(fn () => $this->validate('shirt', 'piece', 1, []), 'method');
    }

    public function test_size_quantities_must_sum_to_piece_quantity(): void
    {
        $out = $this->validate('other', 'piece', 10, ['method' => 'size_quantity', 'sizes' => ['S' => '4', 'M' => '6', 'L' => '0']]);
        $this->assertSame(['S' => 4, 'M' => 6], $out['sizes']);

        $this->assertRejects(fn () => $this->validate('other', 'piece', 10, ['method' => 'size_quantity', 'sizes' => ['S' => '4']]), 'sizes');
    }

    public function test_size_rules(): void
    {
        $this->assertRejects(fn () => $this->validate('shirt', 'pair', 5, ['method' => 'size_quantity', 'sizes' => ['XXXL' => '5']]), 'sizes');
        $this->assertRejects(fn () => $this->validate('shirt', 'pair', 5, ['method' => 'size_quantity', 'sizes' => ['S' => '1.5']]), 'sizes.S');
        $this->assertRejects(fn () => $this->validate('shirt', 'pair', 5, ['method' => 'size_quantity', 'sizes' => ['S' => '-2']]), 'sizes.S');
        $this->assertRejects(fn () => $this->validate('shirt', 'pair', 5, ['method' => 'size_quantity', 'sizes' => ['S' => '', 'M' => '0']]), 'sizes');
        $this->assertRejects(fn () => $this->validate('shirt', 'pair', 5, ['method' => 'size_quantity']), 'sizes');
    }

    public function test_non_strict_units_do_not_need_sizes_to_match_quantity(): void
    {
        $out = $this->validate('shirt', 'set', 10, ['method' => 'size_quantity', 'sizes' => ['M' => '3']]);
        $this->assertSame(['M' => 3], $out['sizes']);
    }

    public function test_every_garment_type_has_a_profile_and_other_has_none(): void
    {
        foreach (['shirt', 'trouser', 'shorts', 'pinafore', 'overall', 'jumpsuit'] as $type) {
            $this->assertNotEmpty(ValidateMeasurementPayload::profileFor($type), $type);
        }
        $this->assertNull(ValidateMeasurementPayload::profileFor('other'));
    }
}
