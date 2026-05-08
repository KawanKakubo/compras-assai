<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\ComprasGov\GovCatalogTaxonomy;
use App\Services\ComprasGov\ComprasGovApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncCatalogEtlCommand extends Command
{
    protected $signature = 'catalog:sync-etl {--limit-pages=50 : Limite máximo de páginas para processar por catálogo}';
    protected $description = 'Executa o processo de ETL robusto e massivo para sincronismo de dados abertos CATMAT/CATSER';

    public function __construct(private readonly ComprasGovApiClient $client)
    {
        parent::__construct();
    }

    public function handle()
    {
        $limitPages = (int)$this->option('limit-pages');
        $this->info("Iniciando processo robusto de ETL de dados abertos do CATMAT/CATSER (Limite: {$limitPages} páginas por catálogo)...");

        Cache::put('etl_progress', [
            'status' => 'processing',
            'progress' => 0,
            'message' => 'Inicializando o ETL massivo...',
            'processed_materials' => 0,
            'processed_services' => 0,
            'current_page' => 0,
            'total_pages' => $limitPages * 2,
            'logs' => ['[ETL] Conexão estabelecida com a API federal de dados abertos.']
        ]);

        $processedMaterials = 0;
        $processedServices = 0;
        $logs = ['[ETL] Conexão estabelecida com a API federal de dados abertos.'];

        // 1. ETL de Materiais (CATMAT)
        $this->info("Fase 1: Extraindo Materiais (CATMAT)...");
        $logs[] = '[CATMAT] Iniciando extração e mapeamento hierárquico...';
        Cache::put('etl_progress', [
            'status' => 'processing',
            'progress' => 5,
            'message' => 'Fase 1: Extraindo Materiais (CATMAT)...',
            'processed_materials' => 0,
            'processed_services' => 0,
            'current_page' => 1,
            'total_pages' => $limitPages * 2,
            'logs' => $logs
        ]);

        for ($page = 1; $page <= $limitPages; $page++) {
            $this->info("Processando Materiais - Página {$page} de {$limitPages}...");
            $logs[] = "[CATMAT] Solicitando página {$page} de {$limitPages}...";
            
            Cache::put('etl_progress', [
                'status' => 'processing',
                'progress' => (int)(5 + (($page / $limitPages) * 45)),
                'message' => "Processando Materiais - Página {$page} de {$limitPages}...",
                'processed_materials' => $processedMaterials,
                'processed_services' => $processedServices,
                'current_page' => $page,
                'total_pages' => $limitPages * 2,
                'logs' => array_slice($logs, -15) // Keep last 15 logs
            ]);

            // Query active material items
            $response = $this->client->get('/modulo-material/4_consultarItemMaterial', [
                'statusItem' => 'true',
                'pagina' => $page,
                'tamanhoPagina' => 100
            ]);

            if (!empty($response['error'])) {
                $logs[] = "[CATMAT ERROR] Página {$page}: " . $response['error'];
                Log::warning("ETL CATMAT Error page {$page}: " . $response['error']);
                // Graceful delay on errors (rate limit)
                sleep(2);
                continue;
            }

            $items = $response['resultado'] ?? [];
            if (empty($items)) {
                $logs[] = '[CATMAT] Sem mais registros ativos de materiais nesta página. Encerrando extração prematuramente.';
                break;
            }

            $pageInserted = 0;
            foreach ($items as $item) {
                try {
                    $itemCode = (string)($item['codigoItem'] ?? '');
                    if (!$itemCode) continue;

                    // Mapeamento de Taxonomia Hierárquica: Grupo > Classe > PDM > Item
                    // Salva os nós de taxonomia se disponíveis no payload do item
                    if (isset($item['codigoGrupo']) && isset($item['nomeGrupo'])) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => 'material',
                            'level_name' => 'group',
                            'code' => (string)$item['codigoGrupo'],
                        ], [
                            'description' => $item['nomeGrupo'],
                        ]);
                    }

                    if (isset($item['codigoClasse']) && isset($item['nomeClasse']) && isset($item['codigoGrupo'])) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => 'material',
                            'level_name' => 'class',
                            'code' => (string)$item['codigoClasse'],
                            'parent_code' => (string)$item['codigoGrupo'],
                        ], [
                            'description' => $item['nomeClasse'],
                        ]);
                    }

                    if (isset($item['codigoPdm']) && isset($item['nomePdm']) && isset($item['codigoClasse'])) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => 'material',
                            'level_name' => 'pdm',
                            'code' => (string)$item['codigoPdm'],
                            'parent_code' => (string)$item['codigoClasse'],
                        ], [
                            'description' => $item['nomePdm'],
                        ]);
                    }

                    // Upsert Item no Banco de Dados Local
                    CatalogItem::updateOrCreate([
                        'item_code' => $itemCode,
                    ], [
                        'description' => $item['descricaoItem'] ?? '',
                        'pdm_code' => $item['codigoPdm'] ?? null,
                        'pdm_name' => $item['nomePdm'] ?? null,
                        'class_code' => $item['codigoClasse'] ?? null,
                        'class_name' => $item['nomeClasse'] ?? null,
                        'group_code' => $item['codigoGrupo'] ?? null,
                        'group_name' => $item['nomeGrupo'] ?? null,
                        'is_sustainable' => $item['itemSustentavel'] ?? false,
                        'is_active' => true,
                        'search_aliases' => trim(($item['nomePdm'] ?? '') . ', ' . ($item['descricaoItem'] ?? '')),
                    ]);

                    $processedMaterials++;
                    $pageInserted++;
                } catch (\Exception $e) {
                    Log::error("ETL Save Material Exception: " . $e->getMessage());
                }
            }

            $logs[] = "[CATMAT] Processados +{$pageInserted} materiais novos/atualizados nesta página.";

            // Rate-limiting safety sleep
            usleep(150000); // 150ms sleep
        }

        // 2. ETL de Serviços (CATSER)
        $this->info("Fase 2: Extraindo Serviços (CATSER)...");
        $logs[] = '[CATSER] Iniciando extração e mapeamento hierárquico...';
        Cache::put('etl_progress', [
            'status' => 'processing',
            'progress' => 50,
            'message' => 'Fase 2: Extraindo Serviços (CATSER)...',
            'processed_materials' => $processedMaterials,
            'processed_services' => 0,
            'current_page' => $limitPages + 1,
            'total_pages' => $limitPages * 2,
            'logs' => $logs
        ]);

        for ($page = 1; $page <= $limitPages; $page++) {
            $this->info("Processando Serviços - Página {$page} de {$limitPages}...");
            $logs[] = "[CATSER] Solicitando página {$page} de {$limitPages}...";
            
            Cache::put('etl_progress', [
                'status' => 'processing',
                'progress' => (int)(50 + (($page / $limitPages) * 45)),
                'message' => "Processando Serviços - Página {$page} de {$limitPages}...",
                'processed_materials' => $processedMaterials,
                'processed_services' => $processedServices,
                'current_page' => $limitPages + $page,
                'total_pages' => $limitPages * 2,
                'logs' => array_slice($logs, -15) // Keep last 15 logs
            ]);

            // Query active service items
            $response = $this->client->get('/modulo-servico/6_consultarItemServico', [
                'statusServico' => 'true',
                'pagina' => $page,
                'tamanhoPagina' => 100
            ]);

            if (!empty($response['error'])) {
                $logs[] = "[CATSER ERROR] Página {$page}: " . $response['error'];
                Log::warning("ETL CATSER Error page {$page}: " . $response['error']);
                sleep(2);
                continue;
            }

            $items = $response['resultado'] ?? [];
            if (empty($items)) {
                $logs[] = '[CATSER] Sem mais registros ativos de serviços nesta página. Encerrando extração prematuramente.';
                break;
            }

            $pageInserted = 0;
            foreach ($items as $item) {
                try {
                    $serviceCode = (string)($item['codigoServico'] ?? '');
                    if (!$serviceCode) continue;

                    // Mapeamento de Taxonomia Hierárquica para Serviços: Seção > Divisão > Grupo > Classe > Subclasse
                    if (isset($item['codigoGrupo']) && isset($item['nomeGrupo'])) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => 'service',
                            'level_name' => 'group',
                            'code' => (string)$item['codigoGrupo'],
                        ], [
                            'description' => $item['nomeGrupo'],
                        ]);
                    }

                    if (isset($item['codigoClasse']) && isset($item['nomeClasse']) && isset($item['codigoGrupo'])) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => 'service',
                            'level_name' => 'class',
                            'code' => (string)$item['codigoClasse'],
                            'parent_code' => (string)$item['codigoGrupo'],
                        ], [
                            'description' => $item['nomeClasse'],
                        ]);
                    }

                    if (isset($item['codigoSubclasse']) && isset($item['nomeSubclasse']) && isset($item['codigoClasse'])) {
                        GovCatalogTaxonomy::updateOrCreate([
                            'catalog_type' => 'service',
                            'level_name' => 'subclass',
                            'code' => (string)$item['codigoSubclasse'],
                            'parent_code' => (string)$item['codigoClasse'],
                        ], [
                            'description' => $item['nomeSubclasse'],
                        ]);
                    }

                    // Upsert Item no Banco de Dados Local
                    CatalogService::updateOrCreate([
                        'service_code' => $serviceCode,
                    ], [
                        'description' => $item['descricaoServico'] ?? $item['nomeServico'] ?? '',
                        'group_code' => $item['codigoGrupo'] ?? null,
                        'group_name' => $item['nomeGrupo'] ?? null,
                        'is_active' => true,
                        'search_aliases' => trim(($item['nomeServico'] ?? '') . ', ' . ($item['descricaoServico'] ?? '')),
                    ]);

                    $processedServices++;
                    $pageInserted++;
                } catch (\Exception $e) {
                    Log::error("ETL Save Service Exception: " . $e->getMessage());
                }
            }

            $logs[] = "[CATSER] Processados +{$pageInserted} serviços novos/atualizados nesta página.";

            // Rate-limiting safety sleep
            usleep(150000); // 150ms sleep
        }

        $logs[] = "[ETL] Processamento completo finalizado com sucesso!";
        $this->info("ETL concluído!");

        Cache::put('etl_progress', [
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Sincronização do catálogo e taxonomia concluída com sucesso!',
            'processed_materials' => $processedMaterials,
            'processed_services' => $processedServices,
            'current_page' => $limitPages * 2,
            'total_pages' => $limitPages * 2,
            'logs' => $logs
        ]);
    }
}
