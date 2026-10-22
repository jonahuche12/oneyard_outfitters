<?php

namespace App\Actions\Measurements;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Throwable;

/**
 * Parses an uploaded .xlsx/.csv into validated measurement data.
 * Values only (formulas never evaluated). Saves nothing. Only the rows and
 * columns that can matter are loaded; rows beyond the limit are never parsed.
 */
class ParseMeasurementSpreadsheet
{
    public const MAX_BYTES = 2097152; // 2 MB
    private const BLANK_ALLOWANCE = 25;

    /**
     * @param  int|null  $limit  maximum people to read (null = configured maximum)
     * @return array{method:string, people:array<int,array>, sizes:array<string,int>, ignored:int, limit:int}
     *
     * @throws ValidationException
     */
    public function individual(string $path, string $originalName, ?string $productType, ?int $limit = null): array
    {
        $profile = ValidateMeasurementPayload::profileFor($productType);
        if (! $profile) {
            $this->fail('Individual measurements are not available for this item. Please use sizes.');
        }

        $max = (int) config('measurements.max_people_per_item');
        $limit = $limit === null ? $max : max(1, min($limit, $max));

        $expected = array_merge(['name'], array_map(fn ($f) => $this->norm($f['label']), array_values($profile)));
        $rows = $this->rows($path, $originalName, count($expected) + 1, 1 + $limit + self::BLANK_ALLOWANCE);
        $this->assertHeader($rows[0] ?? [], $expected);

        $fieldKeys = array_keys($profile);
        $people = [];
        $errors = [];
        $taken = 0;
        $ignored = 0;

        foreach (array_slice($rows, 1, null, true) as $index => $cells) {
            if ($this->blank($cells)) {
                continue;
            }
            if ($taken >= $limit) {
                $ignored++; // counted, never parsed or validated

                continue;
            }
            $taken++;

            $line = $index + 1;
            $measurements = [];
            foreach ($fieldKeys as $i => $key) {
                $measurements[$key] = $this->cell($cells[$i + 1] ?? null);
            }

            try {
                $people[] = app(ValidateMeasurementPayload::class)->person($productType, [
                    'label' => $this->safeText($cells[0] ?? null),
                    'measurements' => $measurements,
                    'notes' => null,
                ]);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    $errors[] = "Row {$line}: " . $messages[0];
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
        if ($people === []) {
            $this->fail('The file has no measurement rows.');
        }

        return [
            'method' => ValidateMeasurementPayload::METHOD_INDIVIDUAL,
            'people' => $people,
            'sizes' => [],
            'ignored' => $ignored,
            'limit' => $limit,
        ];
    }

    /**
     * @return array{method:string, people:array, sizes:array<string,int>, ignored:int, limit:int}
     *
     * @throws ValidationException
     */
    public function sizes(string $path, string $originalName): array
    {
        $allowed = count(config('measurements.sizes'));
        $rows = $this->rows($path, $originalName, 3, 1 + $allowed + self::BLANK_ALLOWANCE);
        $this->assertHeader($rows[0] ?? [], ['size', 'quantity']);

        $sizes = [];
        foreach (array_slice($rows, 1) as $cells) {
            if ($this->blank($cells)) {
                continue;
            }
            $label = strtoupper($this->cell($cells[0] ?? null));
            if (isset($sizes[$label])) {
                $this->fail("Size {$label} appears more than once.");
            }
            $sizes[$label] = $this->cell($cells[1] ?? null);
        }

        try {
            $clean = app(ValidateMeasurementPayload::class)->draftSizes($sizes);
        } catch (ValidationException $e) {
            $msgs = [];
            foreach ($e->errors() as $messages) {
                $msgs[] = $messages[0];
            }
            throw ValidationException::withMessages(['file' => $msgs]);
        }

        return [
            'method' => ValidateMeasurementPayload::METHOD_SIZE_QUANTITY,
            'people' => [],
            'sizes' => $clean,
            'ignored' => 0,
            'limit' => $allowed,
        ];
    }

    /** @return array<int, array<int, mixed>> zero-indexed rows of cell values */
    private function rows(string $path, string $originalName, int $maxCols, int $maxRows): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($ext, ['xlsx', 'csv'], true)) {
            $this->fail('Only .xlsx or .csv files are accepted.');
        }
        if (! is_file($path) || filesize($path) === 0) {
            $this->fail('The file is empty.');
        }
        if (filesize($path) > self::MAX_BYTES) {
            $this->fail('The file is larger than 2 MB.');
        }

        // Structural safety checks happen before any library opens the file.
        app(ScanSpreadsheetUpload::class)->handle($path, $ext);

        try {
            $reader = $ext === 'xlsx' ? new XlsxReader() : new CsvReader();
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new class($maxRows, $maxCols) implements IReadFilter {
                public function __construct(private int $rows, private int $cols)
                {
                }

                public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                {
                    return $row <= $this->rows
                        && Coordinate::columnIndexFromString($columnAddress) <= $this->cols;
                }
            });

            if ($ext === 'xlsx') {
                if (! $reader->canRead($path)) {
                    $this->fail('The file is not a valid Excel workbook.');
                }
                $names = $reader->listWorksheetNames($path);
                if ($names === []) {
                    $this->fail('The file is not a valid Excel workbook.');
                }
                $reader->setLoadSheetsOnly([$names[0]]);
            }

            $data = $reader->load($path)->getSheet(0)->toArray(null, false, false, false);
            if (isset($data[0][0]) && is_string($data[0][0])) {
                $data[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0][0]);
            }

            return $data;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable) {
            $this->fail('The file could not be read. Please use the template and try again.');
        }
    }

    private function assertHeader(array $header, array $expected): void
    {
        $actual = array_map(fn ($h) => $this->norm((string) $h), $header);
        while ($actual !== [] && end($actual) === '') {
            array_pop($actual);
        }

        if ($actual !== $expected) {
            $this->fail('The columns do not match the template. Expected: ' . implode(', ', $expected) . '.');
        }
    }

    private function blank(array $cells): bool
    {
        foreach ($cells as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cell(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function safeText(mixed $value): ?string
    {
        $text = ltrim($this->cell($value), "=+-@\t\r ");

        return $text === '' ? null : $text;
    }

    private function norm(string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => [$message]]);
    }
}
