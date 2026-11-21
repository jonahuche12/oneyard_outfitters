<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('id');
            $table->string('code', 10)->nullable()->after('name');
        });

        DB::table('stock_items')
            ->select(['id', 'category', 'description'])
            ->orderBy('id')
            ->chunkById(200, function ($items): void {
                foreach ($items as $item) {
                    $category = strtoupper((string) ($item->category ?? ''));
                    $category = preg_replace('/[^A-Z0-9]/', '', $category) ?: 'GENERAL';

                    $description = strtoupper((string) ($item->description ?? ''));
                    $description = preg_replace('/[^A-Z0-9]/', '', $description) ?: 'ITEM';
                    $description = substr($description, 0, 9);

                    $baseName = "ONYD-STK-{$category}-{$description}";
                    $name = $baseName;
                    $suffix = 2;

                    while (DB::table('stock_items')->where('name', $name)->exists()) {
                        $name = $baseName.'-'.$suffix;
                        $suffix++;
                    }

                    do {
                        $code = 'STK-'.random_int(100000, 999999);
                    } while (DB::table('stock_items')->where('code', $code)->exists());

                    DB::table('stock_items')
                        ->where('id', $item->id)
                        ->update([
                            'name' => $name,
                            'code' => $code,
                        ]);
                }
            });

        Schema::table('stock_items', function (Blueprint $table) {
            $table->unique('name', 'stock_items_name_unique');
            $table->unique('code', 'stock_items_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropUnique('stock_items_name_unique');
            $table->dropUnique('stock_items_code_unique');
            $table->dropColumn(['name', 'code']);
        });
    }
};
