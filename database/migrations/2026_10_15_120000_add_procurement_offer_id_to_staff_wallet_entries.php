<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_wallet_entries', function (Blueprint $table) {
            $table->foreignId('procurement_offer_id')
                ->nullable()
                ->unique()
                ->constrained('procurement_offers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_wallet_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procurement_offer_id');
        });
    }
};
