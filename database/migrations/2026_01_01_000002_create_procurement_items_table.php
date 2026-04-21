<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procurement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procurement_study_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('item_type', ['material', 'service']);
            $table->string('catalog_code')->nullable();
            $table->string('description');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_value', 15, 2)->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->string('source_system')->nullable();
            $table->string('source_reference')->nullable();
            $table->boolean('is_sustainable')->default(false);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
    }
};