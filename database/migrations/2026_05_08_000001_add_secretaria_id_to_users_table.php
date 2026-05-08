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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('secretaria_id')->nullable()->after('role')->constrained('secretarias')->nullOnDelete();
        });

        // Seed default secretariats and associate existing users
        $configs = config('compras.secretarias') ?? [
            'gabinete' => 'Gabinete do Prefeito',
            'procuradoria' => 'Procuradoria-Geral do Município',
            'administracao' => 'Secretaria de Administração e RH',
            'agricultura' => 'Secretaria de Agricultura, Abastecimento e Meio Ambiente',
            'assistencia_social' => 'Secretaria de Assistência Social',
            'secti' => 'Secretaria de Ciência, Tecnologia e Inovação',
            'cultura' => 'Secretaria de Cultura e Turismo',
            'planejamento_urbano' => 'Secretaria de Engenharias e Planejamento Urbano',
            'educacao' => 'Secretaria de Educação',
            'saude' => 'Secretaria de Saúde',
            'esportes' => 'Secretaria de Esporte e Lazer',
            'financas' => 'Secretaria de Finanças',
            'obras' => 'Secretaria de Obras e Serviços Públicos',
            'trabalho' => 'Secretaria de Trabalho e Renda',
            'suprimentos' => 'Secretaria de Suprimentos',
            'seguranca_alimentar' => 'Secretaria de Segurança Alimentar e Nutrição',
        ];

        $now = now();
        foreach ($configs as $key => $name) {
            $acronym = strtoupper($key);
            // Insert secretariat if not exists
            $secretariaId = DB::table('secretarias')->insertGetId([
                'name' => $name,
                'acronym' => $acronym,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Update matching users
            DB::table('users')
                ->where('secretaria_acronym', $acronym)
                ->orWhere('secretaria_acronym', $key)
                ->orWhere('secretaria_name', $name)
                ->update(['secretaria_id' => $secretariaId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['secretaria_id']);
            $table->dropColumn('secretaria_id');
        });
    }
};
