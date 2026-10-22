<?php

namespace App\Actions\Measurements;

use Illuminate\Validation\ValidationException;
use ZipArchive;

/** Structural safety checks run BEFORE any spreadsheet library opens the file. */
class ScanSpreadsheetUpload
{
    private const MAX_ENTRIES = 200;
    private const MAX_UNCOMPRESSED = 20971520; // 20 MB
    private const MAX_RATIO = 100;
    private const BLOCKED = ['vbaproject', 'externallinks', 'embeddings', 'activex', 'oleobject', 'macrosheets'];

    public function handle(string $path, string $ext): void
    {
        $head = (string) file_get_contents($path, false, null, 0, 8192);
        $isZip = str_starts_with($head, "PK\x03\x04");

        if ($ext === 'csv') {
            if ($isZip || str_contains($head, "\0")) {
                $this->fail('The file is not a plain CSV file.');
            }

            return;
        }

        if (! $isZip) {
            $this->fail('The file is not a valid Excel workbook.');
        }

        $this->scanZip($path);
    }

    private function scanZip(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            $this->fail('The file is not a valid Excel workbook.');
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES) {
                $this->fail('The workbook structure is not accepted.');
            }

            $total = 0;
            $names = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    $this->fail('The file could not be read. Please use the template and try again.');
                }

                $name = (string) $stat['name'];
                $lower = strtolower($name);

                if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                    $this->fail('The workbook structure is not accepted.');
                }

                foreach (self::BLOCKED as $blocked) {
                    if (str_contains($lower, $blocked)) {
                        $this->fail('Workbooks with macros, external links or embedded objects are not accepted.');
                    }
                }

                $size = (int) $stat['size'];
                $total += $size;
                if ($total > self::MAX_UNCOMPRESSED) {
                    $this->fail('The workbook is too large when unpacked.');
                }

                $compressed = (int) $stat['comp_size'];
                if ($size > 1048576 && $compressed > 0 && ($size / $compressed) > self::MAX_RATIO) {
                    $this->fail('The workbook is not accepted.');
                }

                if (preg_match('/\.(xml|rels)$/i', $name)) {
                    $stream = $zip->getStream($name);
                    if ($stream) {
                        $chunk = (string) fread($stream, 2048);
                        fclose($stream);
                        if (preg_match('/<!(DOCTYPE|ENTITY)/i', $chunk)) {
                            $this->fail('The workbook is not accepted.');
                        }
                    }
                }

                $names[] = $name;
            }

            if (! in_array('[Content_Types].xml', $names, true) || ! in_array('xl/workbook.xml', $names, true)) {
                $this->fail('The file is not a valid Excel workbook.');
            }
        } finally {
            $zip->close();
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => [$message]]);
    }
}
