<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('compras:sync-pdms')]
#[Description('Sincroniza todos os Padrões Descritivos de Material (PDMs) do Compras.gov para o banco local.')]
class SyncComprasPdms extends Command
{
    public function handle(\App\Services\ComprasGov\ComprasGovApiClient $client)
    {
        $this->info('Iniciando sincronização de PDMs do Compras.gov.br...');
        
        $page = 1;
        $totalItems = 0;
        
        do {
            $this->info("Buscando página {$page}...");
            $response = $client->get('/modulo-material/3_consultarPdmMaterial', [
                'tamanhoPagina' => 500,
                'pagina' => $page,
                'statusPdm' => true,
            ]);

            if (empty($response['resultado'])) {
                break;
            }

            $pdms = $response['resultado'];
            $dataToInsert = [];

            foreach ($pdms as $pdm) {
                $dataToInsert[] = [
                    'codigoGrupo' => $pdm['codigoGrupo'] ?? 0,
                    'nomeGrupo' => mb_substr($pdm['nomeGrupo'] ?? '', 0, 255),
                    'codigoClasse' => $pdm['codigoClasse'] ?? 0,
                    'nomeClasse' => mb_substr($pdm['nomeClasse'] ?? '', 0, 255),
                    'codigoPdm' => $pdm['codigoPdm'] ?? 0,
                    'nomePdm' => mb_substr($pdm['nomePdm'] ?? '', 0, 255),
                    'statusPdm' => $pdm['statusPdm'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            \Illuminate\Support\Facades\DB::table('compras_pdms')->upsert(
                $dataToInsert,
                ['codigoPdm'],
                ['nomeGrupo', 'codigoClasse', 'nomeClasse', 'nomePdm', 'statusPdm', 'updated_at']
            );

            $totalItems += count($dataToInsert);
            $this->info("Salvos " . count($dataToInsert) . " PDMs. Total até agora: {$totalItems}");

            $totalRegistros = $response['totalRegistros'] ?? 0;
            $totalPages = ceil($totalRegistros / 500);

            $page++;
        } while ($page <= $totalPages && count($pdms) > 0);

        $this->info('Sincronização concluída! Total de PDMs salvos: ' . $totalItems);
    }
}
