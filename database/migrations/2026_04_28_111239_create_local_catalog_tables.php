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
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->integer('item_code')->unique();
            $table->string('description', 2000);
            $table->integer('pdm_code')->index();
            $table->string('pdm_name');
            $table->integer('class_code')->index();
            $table->string('class_name');
            $table->integer('group_code')->index();
            $table->string('group_name');
            $table->boolean('is_sustainable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Full-text search index (if using PostgreSQL)
            // For SQLite we just use regular indexes on description
            $table->index('description');
        });

        Schema::create('catalog_services', function (Blueprint $table) {
            $table->id();
            $table->integer('service_code')->unique();
            $table->string('description', 2000);
            $table->integer('group_code')->index();
            $table->string('group_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
        Schema::dropIfExists('catalog_services');
    }
};
