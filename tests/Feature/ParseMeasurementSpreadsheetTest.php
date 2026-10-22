<?php

namespace Tests\Feature;

use App\Actions\Measurements\ParseMeasurementSpreadsheet;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ParseMeasurementSpreadsheetTest extends TestCase
{
    private array $temp = [];

    protected function tearDown(): void
    {
        foreach ($this->temp as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function csv(array $rows, string $ext = 'csv'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ms') . '.' . $ext;
        $h = fopen($path, 'w');
        foreach ($rows as $r) {
            fputcsv($h, $r);
        }
        fclose($h);

        return $this->temp[] = $path;
    }

    private const SHIRT_HEADER = ['Name', 'Neck', 'Chest', 'Waist', 'Shoulder width', 'Sleeve length', 'Shirt length'];

    private function parse(string $path, string $name = 'm.csv'): array
    {
        return app(ParseMeasurementSpreadsheet::class)->individual($path, $name, 'shirt');
    }

    private function errors(callable $fn): array
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            return $e->errors()['file'];
        }
        $this->fail('Expected a validation failure.');
    }

    public function test_valid_csv_returns_people(): void
    {
        $path = $this->csv([self::SHIRT_HEADER, ['Ada', 15, 38, 32, 17, 24, 29], ['Bola', 16, 40, 34, 18, 25, 30]]);

        $r = $this->parse($path);

        $this->assertSame('individual', $r['method']);
        $this->assertCount(2, $r['people']);
        $this->assertSame('Ada', $r['people'][0]['label']);
        $this->assertSame('32.00', $r['people'][0]['measurements']['waist']);
    }

    public function test_valid_xlsx_returns_people(): void
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([self::SHIRT_HEADER, ['Ada', 15, 38, 32, 17, 24, 29]]);
        $path = $this->temp[] = tempnam(sys_get_temp_dir(), 'ms') . '.xlsx';
        (new Xlsx($book))->save($path);

        $r = $this->parse($path, 'm.xlsx');

        $this->assertCount(1, $r['people']);
    }

    public function test_wrong_columns_are_rejected(): void
    {
        $path = $this->csv([['Name', 'Neck'], ['Ada', 15]]);

        $this->assertStringContainsString('columns do not match', $this->errors(fn () => $this->parse($path))[0]);
    }

    public function test_bad_row_rejects_the_whole_file_with_row_number(): void
    {
        $path = $this->csv([self::SHIRT_HEADER, ['Ada', 15, 38, 32, 17, 24, 29], ['Bola', 15, 38, 'abc', 17, 24, 29]]);

        $errors = $this->errors(fn () => $this->parse($path));

        $this->assertStringStartsWith('Row 3:', $errors[0]);
    }

    public function test_blank_rows_are_skipped_and_empty_file_rejected(): void
    {
        $ok = $this->csv([self::SHIRT_HEADER, ['', '', '', '', '', '', ''], ['Ada', 15, 38, 32, 17, 24, 29]]);
        $this->assertCount(1, $this->parse($ok)['people']);

        $none = $this->csv([self::SHIRT_HEADER]);
        $this->assertStringContainsString('no measurement rows', $this->errors(fn () => $this->parse($none))[0]);
    }

    public function test_formula_prefix_is_stripped_from_the_name(): void
    {
        $path = $this->csv([self::SHIRT_HEADER, ['=HYPERLINK("x")', 15, 38, 32, 17, 24, 29]]);

        $this->assertStringStartsNotWith('=', (string) $this->parse($path)['people'][0]['label']);
    }

    public function test_wrong_extension_and_oversize_are_rejected(): void
    {
        $path = $this->csv([self::SHIRT_HEADER], 'txt');
        $this->assertStringContainsString('.xlsx or .csv', $this->errors(fn () => $this->parse($path, 'm.exe'))[0]);

        $big = $this->temp[] = tempnam(sys_get_temp_dir(), 'ms') . '.csv';
        file_put_contents($big, str_repeat('a', ParseMeasurementSpreadsheet::MAX_BYTES + 1));
        $this->assertStringContainsString('larger than 2 MB', $this->errors(fn () => $this->parse($big))[0]);
    }

    public function test_garbage_xlsx_is_rejected(): void
    {
        $path = $this->temp[] = tempnam(sys_get_temp_dir(), 'ms') . '.xlsx';
        file_put_contents($path, 'not a workbook');

        $this->errors(fn () => $this->parse($path, 'm.xlsx'));
        $this->assertTrue(true);
    }

    public function test_sizes_template_parses_and_rejects_unknown_size(): void
    {
        $ok = $this->csv([['Size', 'Quantity'], ['S', 2], ['M', 3]]);
        $r = app(ParseMeasurementSpreadsheet::class)->sizes($ok, 's.csv');
        $this->assertSame(['S' => 2, 'M' => 3], $r['sizes']);

        $bad = $this->csv([['Size', 'Quantity'], ['ZZZ', 2]]);
        $this->errors(fn () => app(ParseMeasurementSpreadsheet::class)->sizes($bad, 's.csv'));
        $this->assertTrue(true);
    }

    public function test_item_without_profile_cannot_use_individual_upload(): void
    {
        $path = $this->csv([self::SHIRT_HEADER]);

        $this->errors(fn () => app(ParseMeasurementSpreadsheet::class)->individual($path, 'm.csv', 'other'));
        $this->assertTrue(true);
    }

    public function test_utf8_bom_on_first_header_cell_is_ignored(): void
    {
        $path = $this->csv([self::SHIRT_HEADER, ['Ada', 15, 38, 32, 17, 24, 29]]);
        file_put_contents($path, "\xEF\xBB\xBF" . file_get_contents($path));

        $this->assertCount(1, $this->parse($path)['people']);
    }
}
