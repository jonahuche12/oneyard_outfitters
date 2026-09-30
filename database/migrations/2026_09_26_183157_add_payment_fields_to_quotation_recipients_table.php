<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_recipients', function (Blueprint $table) {
            $table->unsignedTinyInteger('payment_percentage')
                ->nullable()
                ->after('response_status');

            $table->decimal('payment_amount', 15, 2)
                ->nullable()
                ->after('payment_percentage');

            $table->decimal('amount_paid', 15, 2)
                ->default(0)
                ->after('payment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_recipients', function (Blueprint $table) {
            $table->dropColumn([
                'payment_percentage',
                'payment_amount',
                'amount_paid',
            ]);
        });
    }
};
