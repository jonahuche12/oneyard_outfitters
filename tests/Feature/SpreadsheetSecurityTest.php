<?php

namespace Tests\Feature;

use App\Actions\Measurements\ScanSpreadsheetUpload;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class SpreadsheetSecurityTest extends TestCase
{
    private array $temp = [];

    protected function tearDown(): void
    {
        foreach ($this->temp as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function zip(array $entries): string
    {
        $path = $this->temp[] = tempnam(sys_get_temp_dir(), 'sx') . '.xlsx';
        $z = new ZipArchive();
        $z->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $name => $body) {
            $z->addFromString($name, $body);
        }
        $z->close();

        return $path;
    }

    private function base(array $extra = []): array
    {
        return ['[Content_Types].xml' => '<Types/>', 'xl/workbook.xml' => '<workbook/>'] + $extra;
    }

    private function rejects(string $path, string $ext): void
    {
        try {
            app(ScanSpreadsheetUpload::class)->handle($path, $ext);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());

            return;
        }
        $this->fail('Expected the file to be rejected.');
    }

    public function test_clean_workbook_passes(): void
    {
        app(ScanSpreadsheetUpload::class)->handle($this->zip($this->base()), 'xlsx');
        $this->assertTrue(true);
    }

    public function test_macro_workbook_is_rejected(): void
    {
        $this->rejects($this->zip($this->base(['xl/vbaProject.bin' => 'x'])), 'xlsx');
    }

    public function test_external_links_and_embeddings_are_rejected(): void
    {
        $this->rejects($this->zip($this->base(['xl/externalLinks/externalLink1.xml' => '<x/>'])), 'xlsx');
        $this->rejects($this->zip($this->base(['xl/embeddings/oleObject1.bin' => 'x'])), 'xlsx');
    }

    public function test_doctype_and_entity_declarations_are_rejected(): void
    {
        $xml = '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY x SYSTEM "file:///etc/passwd">]><a/>';
        $this->rejects($this->zip($this->base(['xl/sharedStrings.xml' => $xml])), 'xlsx');
    }

    public function test_path_traversal_entry_is_rejected(): void
    {
        $this->rejects($this->zip($this->base(['../evil.xml' => '<a/>'])), 'xlsx');
    }

    public function test_workbook_without_required_parts_is_rejected(): void
    {
        $this->rejects($this->zip(['readme.txt' => 'hello']), 'xlsx');
    }

    public function test_non_zip_renamed_to_xlsx_is_rejected(): void
    {
        $path = $this->temp[] = tempnam(sys_get_temp_dir(), 'sx') . '.xlsx';
        file_put_contents($path, 'plain text');
        $this->rejects($path, 'xlsx');
    }

    public function test_binary_or_zip_renamed_to_csv_is_rejected(): void
    {
        $bin = $this->temp[] = tempnam(sys_get_temp_dir(), 'sx') . '.csv';
        file_put_contents($bin, "Name,Neck\n" . chr(0) . "binary");
        $this->rejects($bin, 'csv');

        $this->rejects($this->zip($this->base()), 'csv');
    }
}
