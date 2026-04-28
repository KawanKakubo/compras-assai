<?php

namespace App\Services\ComprasGov;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PncpService
{
    private string $baseUrl = 'https://pncp.gov.br/api/pncp/v1';

    /**
     * Busca contratações por período.
     */
    public function getContratacoes(string $dataInicial, int $pagina = 1)
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/contratacoes", [
                'dataInicial' => $dataInicial,
                'pagina' => $pagina,
                'tamanhoPagina' => 50
            ]);

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            Log::error("Erro PNCP Contratacoes: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca os itens de uma contratação específica.
     */
    public function getItensContratacao(string $cnpj, int $ano, int $sequencial)
    {
        try {
            $response = Http::timeout(20)->get("{$this->baseUrl}/orgaos/{$cnpj}/contratacoes/{$ano}/{$sequencial}/itens");
            return $response->successful() ? $response->json() : [];
        } catch (\Exception $e) {
            Log::error("Erro PNCP Itens: " . $e->getMessage());
            return [];
        }
    }
}
