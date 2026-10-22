<?php

namespace Tests\Feature;

use App\Actions\Measurements\ParseMeasurementSpreadsheet;
use Tests\TestCase;

class ParseLimitTest extends TestCase
{
    private array $temp = [];

    private const HEADER = ['Name', 'Neck', 'Chest', 'Waist', 'Shoulder width', 'Sleeve length', 'Shirt length'];

    protected function tearDown(): void
    {
        foreach ($this->temp as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function csv(array $rows): string
    {
        $path = $this->temp[] = tempnam(sys_get_temp_dir(), 'pl') . '.csv';
        $h = fopen($path, 'w');
        foreach ($rows as $r) {
            fputcsv($h, $r);
        }
        fclose($h);

        return $path;
    }

    private function person(string $name, $waist = 32): array
    {
        return [$name, 15, 38, $waist, 17, 24, 29];
    }

    private function parse(string $path, ?int $limit): array
    {
        return app(ParseMeasurementSpreadsheet::class)->individual($path, 'm.csv', 'shirt', $limit);
    }

    public function test_rows_beyond_the_limit_are_not_used(): void
    {
        $rows = [self::HEADER];
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $n) {
            $rows[] = $this->person($n);
        }

        $r = $this->parse($this->csv($rows), 3);

        $this->assertSame(['A', 'B', 'C'], array_column($r['people'], 'label'));
        $this->assertSame(3, $r['ignored']);
        $this->assertSame(3, $r['limit']);
    }

    public function test_invalid_rows_beyond_the_limit_are_never_validated(): void
    {
        $rows = [self::HEADER, $this->person('A'), $this->person('B'), $this->person('Bad', 'abc')];

        $r = $this->parse($this->csv($rows), 2);

        $this->assertCount(2, $r['people']);
        $this->assertSame(1, $r['ignored']);
    }

    public function test_invalid_row_inside_the_limit_is_still_rejected(): void
    {
        $rows = [self::HEADER, $this->person('A'), $this->person('Bad', 'abc')];

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->parse($this->csv($rows), 5);
    }

    public function test_blank_rows_do_not_count_toward_the_limit(): void
    {
        $rows = [self::HEADER, $this->person('A'), ['', '', '', '', '', '', ''], $this->person('B'), $this->person('C')];

        $r = $this->parse($this->csv($rows), 2);

        $this->assertSame(['A', 'B'], array_column($r['people'], 'label'));
        $this->assertSame(1, $r['ignored']);
    }

    public function test_fewer_rows_than_the_limit_is_fine(): void
    {
        $r = $this->parse($this->csv([self::HEADER, $this->person('A')]), 30);

        $this->assertCount(1, $r['people']);
        $this->assertSame(0, $r['ignored']);
    }
}
