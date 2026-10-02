<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_plans', function (Blueprint $table): void {
            $table->foreignId('coordinator_checked_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('coordinator_checked_at')
                ->nullable()
                ->after('coordinator_checked_by');

            $table->text('coordinator_check_notes')
                ->nullable()
                ->after('coordinator_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('production_plans', function (Blueprint $table): void {
            $table->dropForeign(['coordinator_checked_by']);
            $table->dropColumn([
                'coordinator_checked_by',
                'coordinator_checked_at',
                'coordinator_check_notes',
            ]);
        });
    }
};
