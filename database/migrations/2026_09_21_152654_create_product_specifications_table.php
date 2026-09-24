<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('specification_date');

            $table->string('item_name', 200);

            $table->string('product_type', 50);

            $table->text('description');

            $table->string('unit', 50);

            $table->decimal('unit_price', 15, 2);

            $table->date('price_updated_at')->nullable();

            $table->text('material')->nullable();

            $table->text('material_details')->nullable();

            $table->text('design_details')->nullable();

            $table->text('size_details')->nullable();

            $table->text('branding_details')->nullable();

            $table->text('quality_requirements')->nullable();

            $table->text('special_instructions')->nullable();

            $table->string('status', 30)->default('draft');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index('organization_id');
            $table->index('contact_id');
            $table->index('created_by');
            $table->index('specification_date');
            $table->index('product_type');
            $table->index('status');

            $table->index([
                'organization_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};