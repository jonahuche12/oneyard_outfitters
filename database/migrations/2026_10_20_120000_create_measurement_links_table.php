<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders', 'id', 'ml_order_fk')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts', 'id', 'ml_contact_fk')->restrictOnDelete();
            $table->char('token_hash', 64)->unique('ml_token_hash_unique');
            $table->timestamp('expires_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users', 'id', 'ml_revoked_by_fk')->nullOnDelete();
            $table->text('revoke_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users', 'id', 'ml_created_by_fk')->nullOnDelete();
            // 1 while the link is the active one; NULL once revoked or replaced.
            $table->unsignedTinyInteger('is_active_slot')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'contact_id', 'is_active_slot'], 'ml_active_slot_unique');
            $table->index('expires_at', 'ml_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_links');
    }
};
