<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('production_activities')
            ->whereIn('name', ['Quality Control', 'Delivery'])
            ->update([
                'is_required' => false,
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('production_activities')
            ->where('name', 'Quality Control')
            ->update([
                'is_required' => true,
                'is_active' => true,
                'updated_at' => now(),
            ]);

        DB::table('production_activities')
            ->where('name', 'Delivery')
            ->update([
                'is_required' => true,
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
