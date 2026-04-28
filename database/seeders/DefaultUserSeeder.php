<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        \App\Models\User::create([
            'name' => 'Administrador',
            'email' => 'admin@assai.pr.gov.br',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Elaborador (Secretaria de Saúde)
        \App\Models\User::create([
            'name' => 'Membro Saúde',
            'email' => 'elaborador.saude@assai.pr.gov.br',
            'password' => \Illuminate\Support\Facades\Hash::make('senha123'),
            'role' => 'elaborador',
            'secretaria_name' => 'Secretaria Municipal de Saúde',
            'secretaria_acronym' => 'SAUDE',
        ]);

        // Secretário (Secretaria de Saúde)
        \App\Models\User::create([
            'name' => 'Secretário de Saúde',
            'email' => 'secretario.saude@assai.pr.gov.br',
            'password' => \Illuminate\Support\Facades\Hash::make('senha123'),
            'role' => 'secretario',
            'secretaria_name' => 'Secretaria Municipal de Saúde',
            'secretaria_acronym' => 'SAUDE',
        ]);

        // Gabinete
        \App\Models\User::create([
            'name' => 'Gabinete Prefeito',
            'email' => 'gabinete@assai.pr.gov.br',
            'password' => \Illuminate\Support\Facades\Hash::make('senha123'),
            'role' => 'gabinete',
        ]);

        // Compras
        \App\Models\User::create([
            'name' => 'Departamento de Compras',
            'email' => 'compras@assai.pr.gov.br',
            'password' => \Illuminate\Support\Facades\Hash::make('senha123'),
            'role' => 'compras',
        ]);
    }
}
