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
        Schema::create('compras_pdms', function (Blueprint $table) {
            $table->id();
            $table->integer('codigoGrupo')->index();
            $table->string('nomeGrupo');
            $table->integer('codigoClasse')->index();
            $table->string('nomeClasse');
            $table->integer('codigoPdm')->unique();
            $table->string('nomePdm')->index();
            $table->boolean('statusPdm')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_pdms');
    }
};
