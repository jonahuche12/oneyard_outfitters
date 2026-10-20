<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_submissions', function (Blueprint $table) {
            $table->foreignId('measurement_link_id')->nullable()
                ->constrained('measurement_links', 'id', 'ms_link_fk')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('measurement_submissions', function (Blueprint $table) {
            $table->dropForeign('ms_link_fk');
            $table->dropColumn('measurement_link_id');
        });
    }
};
