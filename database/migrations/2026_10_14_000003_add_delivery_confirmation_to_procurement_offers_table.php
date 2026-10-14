<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_offers', function (Blueprint $table): void {
            $table->decimal('delivered_quantity', 12, 2)
                ->nullable()
                ->after('submitted_at');

            $table->foreignId('delivered_by')
                ->nullable()
                ->after('delivered_quantity')
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('delivered_at')
                ->nullable()
                ->after('delivered_by');

            $table->text('delivery_notes')
                ->nullable()
                ->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_offers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivered_by');
            $table->dropColumn([
                'delivered_quantity',
                'delivered_at',
                'delivery_notes',
            ]);
        });
    }
};
