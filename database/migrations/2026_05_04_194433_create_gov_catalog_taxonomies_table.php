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
        Schema::create('gov_catalog_taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('catalog_type'); // 'material' or 'service'
            $table->string('level_name');   // 'group', 'class', 'pdm', 'section', 'division', etc.
            $table->string('parent_code')->nullable()->index(); // code of the parent element
            $table->string('code')->index(); // code of the current element
            $table->text('description');
            $table->json('extra_data')->nullable(); // For units of measure, characteristics, etc.
            
            $table->timestamps();

            // Ensure we don't have duplicates for the same hierarchy branch
            $table->unique(['catalog_type', 'level_name', 'parent_code', 'code'], 'catalog_taxonomy_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gov_catalog_taxonomies');
    }
};
