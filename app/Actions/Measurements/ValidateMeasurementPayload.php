<?php

namespace App\Actions\Measurements;

use Illuminate\Validation\ValidationException;

class ValidateMeasurementPayload
{
    public const METHOD_INDIVIDUAL = 'individual';
    public const METHOD_SIZE_QUANTITY = 'size_quantity';

    /** Units where one unit is exactly one person's garment. */
    private const STRICT_UNITS = ['piece'];

    public static function profileFor(?string $productType): ?array
    {
        return $productType ? config('measurements.profiles.' . $productType) : null;
    }

    /**
     * @param  array{method?:mixed, people?:mixed, sizes?:mixed}  $input
     * @return array{method:string, people:array<int,array>, sizes:array<string,int>}
     *
     * @throws ValidationException
     */
    public function handle(?string $productType, string $unit, float|int|string $quantity, array $input): array
    {
        $method = $input['method'] ?? null;
        $quantity = (int) round((float) $quantity);
        $strict = in_array(strtolower($unit), self::STRICT_UNITS, true);
        $profile = self::profileFor($productType);

        if (! in_array($method, [self::METHOD_INDIVIDUAL, self::METHOD_SIZE_QUANTITY], true)) {
            $this->fail('method', 'Choose how you want to provide measurements.');
        }

        if ($method === self::METHOD_INDIVIDUAL) {
            if (! $profile) {
                $this->fail('method', 'Individual measurements are not available for this item. Please use sizes.');
            }

            return ['method' => $method, 'people' => $this->people($profile, $strict, $quantity, $input['people'] ?? null), 'sizes' => []];
        }

        return ['method' => $method, 'people' => [], 'sizes' => $this->sizes($strict, $quantity, $input['sizes'] ?? null)];
    }

    private function people(array $profile, bool $strict, int $quantity, mixed $people): array
    {
        if (! is_array($people) || $people === []) {
            $this->fail('people', 'Add at least one person.');
        }

        $people = array_values($people);
        $max = (int) config('measurements.max_people_per_item');

        if (count($people) > $max) {
            $this->fail('people', "No more than {$max} people can be submitted at once.");
        }

        if ($strict && count($people) !== $quantity) {
            $this->fail('people', "This item has a quantity of {$quantity}, so measurements are needed for exactly {$quantity} people.");
        }

        $clean = [];
        foreach ($people as $i => $person) {
            $n = $i + 1;
            if (! is_array($person)) {
                $this->fail("people.$i", "Person {$n} is invalid.");
            }

            $values = [];
            foreach ($profile as $key => $rule) {
                $raw = $person['measurements'][$key] ?? null;
                $values[$key] = $this->number($raw, $rule, "Person {$n}: {$rule['label']}", "people.$i.$key");
            }

            $clean[] = [
                'label' => $this->text($person['label'] ?? null, 255),
                'notes' => $this->text($person['notes'] ?? null, 1000),
                'measurements' => $values,
                'position' => $n,
            ];
        }

        return $clean;
    }

    private function sizes(bool $strict, int $quantity, mixed $sizes): array
    {
        if (! is_array($sizes)) {
            $this->fail('sizes', 'Enter a quantity for at least one size.');
        }

        $allowed = config('measurements.sizes');
        $clean = [];

        foreach ($sizes as $label => $qty) {
            if (is_array($qty) || $qty === null || $qty === '' || (is_string($qty) && trim($qty) === '0')) {
                continue; // blank or zero rows are ignored
            }
            if (! in_array($label, $allowed, true)) {
                $this->fail('sizes', 'Unknown size selected.');
            }
            if (! is_numeric($qty) || (string) (int) $qty !== trim((string) $qty) || (int) $qty < 1) {
                $this->fail("sizes.$label", "Quantity for size {$label} must be a whole number of at least 1.");
            }
            $clean[$label] = (int) $qty;
        }

        if ($clean === []) {
            $this->fail('sizes', 'Enter a quantity for at least one size.');
        }

        if ($strict && array_sum($clean) !== $quantity) {
            $this->fail('sizes', 'Size quantities add up to ' . array_sum($clean) . ' but the order quantity is ' . $quantity . '.');
        }

        return $clean;
    }

    private function number(mixed $raw, array $rule, string $label, string $field): string
    {
        if (is_array($raw) || $raw === null || trim((string) $raw) === '') {
            $this->fail($field, "{$label} is required.");
        }

        $raw = trim((string) $raw);
        $decimals = (int) config('measurements.max_decimals');

        if (! preg_match('/^\d+(\.\d{1,' . $decimals . '})?$/', $raw)) {
            $this->fail($field, "{$label} must be a number in inches with at most {$decimals} decimals.");
        }

        $value = (float) $raw;
        if ($value < $rule['min'] || $value > $rule['max']) {
            $this->fail($field, "{$label} must be between {$rule['min']} and {$rule['max']} inches.");
        }

        return number_format($value, $decimals, '.', '');
    }

    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
