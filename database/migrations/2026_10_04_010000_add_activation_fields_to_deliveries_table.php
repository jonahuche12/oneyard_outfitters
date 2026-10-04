<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->string('payment_arrangement')
                ->nullable()
                ->after('status');

            $table->timestamp('activated_at')
                ->nullable()
                ->after('payment_arrangement');

            $table->index('payment_arrangement');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table): void {
            $table->dropIndex([
                'payment_arrangement',
            ]);

            $table->dropColumn([
                'payment_arrangement',
                'activated_at',
            ]);
        });
    }
};
