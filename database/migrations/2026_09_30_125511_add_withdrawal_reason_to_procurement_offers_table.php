<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_offers', function (Blueprint $table): void {
            $table->text('withdrawal_reason')
                ->nullable()
                ->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_offers', function (Blueprint $table): void {
            $table->dropColumn('withdrawal_reason');
        });
    }
};
