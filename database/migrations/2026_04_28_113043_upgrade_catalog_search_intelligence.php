<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Habilita a extensão de busca por similaridade no PostgreSQL
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->text('search_aliases')->nullable(); // Ex: "carro, uno, fiat"
            $table->index('search_aliases');
        });

        Schema::table('catalog_services', function (Blueprint $table) {
            $table->text('search_aliases')->nullable();
            $table->index('search_aliases');
        });
        
        // Cria índices GIN para busca ultra rápida por similaridade
        DB::statement('CREATE INDEX catalog_items_description_trgm_idx ON catalog_items USING gin (description gin_trgm_ops)');
        DB::statement('CREATE INDEX catalog_items_aliases_trgm_idx ON catalog_items USING gin (search_aliases gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('search_aliases');
        });

        Schema::table('catalog_services', function (Blueprint $table) {
            $table->dropColumn('search_aliases');
        });
    }
};
