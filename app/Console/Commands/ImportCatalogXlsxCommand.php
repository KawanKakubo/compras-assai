<?php

namespace App\Console\Commands;

use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\ComprasGov\GovCatalogTaxonomy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCatalogXlsxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:import-xlsx {filePath} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa uma lista completa do CATMAT ou CATSER a partir de um arquivo Excel (.xlsx)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('filePath');
        $type = strtolower($this->argument('type'));

        if (!file_exists($filePath)) {
            $this->logError("Arquivo não localizado no caminho: $filePath");
            return 1;
        }

        if (!in_array($type, ['material', 'service'])) {
            $this->logError("Tipo inválido. Escolha 'material' ou 'service'.");
            return 1;
        }

        $this->info("Iniciando importação de $type a partir de: $filePath");
        $this->updateProgress(0, "Iniciando processamento da planilha...", 0, 0);

        try {
            // Set up memory limit and timeout for large spreadsheets
            ini_set('memory_limit', '1024M');
            set_time_limit(0);

            $this->logInfo("Carregando cabeçalhos e estrutura do arquivo... [Isso pode levar alguns segundos]");
            
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $this->logInfo("Arquivo carregado. Total de linhas: " . number_format($highestRow, 0, ',', '.'));

            if ($type === 'material') {
                $this->importMaterials($worksheet, $highestRow);
            } else {
                $this->importServices($worksheet, $highestRow);
            }

            // Cleanup temp file if located in tmp_uploads
            if (str_contains($filePath, 'tmp_uploads')) {
                @unlink($filePath);
            }

            return 0;

        } catch (\Exception $e) {
            $this->logError("Erro crítico durante a importação: " . $e->getMessage());
            $this->updateProgress(0, "Erro: " . $e->getMessage(), 0, 0, 'error');
            return 1;
        }
    }

    /**
     * Parse and import materials from catmat.xlsx
     */
    private function importMaterials($worksheet, $highestRow)
    {
        // Headers are on Row 2, Data starts on Row 3
        $startRow = 3;
        $batchSize = 250;
        
        $processed = 0;
        $taxonomiesAdded = 0;
        $itemsAdded = 0;

        $materialsBatch = [];
        $taxonomiesBatch = [];

        // Cache existing codes in memory to prevent duplicate DB requests in loops
        $existingItems = CatalogItem::pluck('item_code')->flip()->toArray();
        $existingTaxonomies = GovCatalogTaxonomy::where('catalog_type', 'material')
            ->get(['level_name', 'code'])
            ->mapWithKeys(fn($item) => [$item->level_name . '_' . $item->code => true])
            ->toArray();

        for ($row = $startRow; $row <= $highestRow; $row++) {
            // Read columns row-by-row
            $groupCode  = $this->cleanString($worksheet->getCell("A$row")->getValue());
            $groupName  = $this->cleanString($worksheet->getCell("B$row")->getValue());
            $classCode  = $this->cleanString($worksheet->getCell("C$row")->getValue());
            $className  = $this->cleanString($worksheet->getCell("D$row")->getValue());
            $pdmCode    = $this->cleanString($worksheet->getCell("E$row")->getValue());
            $pdmName    = $this->cleanString($worksheet->getCell("F$row")->getValue());
            $itemCode   = $this->cleanString($worksheet->getCell("G$row")->getValue());
            $description= $this->cleanString($worksheet->getCell("H$row")->getValue());

            if (empty($itemCode) || empty($description)) {
                continue;
            }

            // Map Taxonomy Nodes safely
            if (!empty($groupCode) && !empty($groupName) && !isset($existingTaxonomies['group_' . $groupCode])) {
                GovCatalogTaxonomy::updateOrCreate(
                    ['catalog_type' => 'material', 'level_name' => 'group', 'code' => $groupCode],
                    ['description' => $groupName]
                );
                $existingTaxonomies['group_' . $groupCode] = true;
                $taxonomiesAdded++;
            }

            if (!empty($classCode) && !empty($className) && !isset($existingTaxonomies['class_' . $classCode])) {
                GovCatalogTaxonomy::updateOrCreate(
                    ['catalog_type' => 'material', 'level_name' => 'class', 'code' => $classCode],
                    ['description' => $className, 'parent_code' => $groupCode]
                );
                $existingTaxonomies['class_' . $classCode] = true;
                $taxonomiesAdded++;
            }

            if (!empty($pdmCode) && !empty($pdmName) && !isset($existingTaxonomies['pdm_' . $pdmCode])) {
                GovCatalogTaxonomy::updateOrCreate(
                    ['catalog_type' => 'material', 'level_name' => 'pdm', 'code' => $pdmCode],
                    ['description' => $pdmName, 'parent_code' => $classCode]
                );
                $existingTaxonomies['pdm_' . $pdmCode] = true;
                $taxonomiesAdded++;
            }

            // Prepare Catalog Item
            $itemData = [
                'item_code' => $itemCode,
                'description' => $description,
                'pdm_code' => (int)$pdmCode,
                'pdm_name' => $pdmName,
                'class_code' => (int)$classCode,
                'class_name' => $className,
                'group_code' => (int)$groupCode,
                'group_name' => $groupName,
                'is_active' => true,
                'is_sustainable' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            // Add or update
            if (isset($existingItems[$itemCode])) {
                CatalogItem::where('item_code', $itemCode)->update(array_intersect_key($itemData, array_flip([
                    'description', 'pdm_code', 'pdm_name', 'class_code', 'class_name', 'group_code', 'group_name'
                ])));
            } else {
                $materialsBatch[] = $itemData;
                $existingItems[$itemCode] = true;
                $itemsAdded++;
            }

            $processed++;

            // Flush batches
            if (count($materialsBatch) >= $batchSize) {
                CatalogItem::insert($materialsBatch);
                $materialsBatch = [];

                $pct = min(100, round(($row / $highestRow) * 100));
                $this->logInfo("[CATMAT] Processadas " . number_format($row, 0, ',', '.') . " de " . number_format($highestRow, 0, ',', '.') . " linhas...");
                $this->updateProgress($pct, "Importando materiais (CATMAT)... Processadas " . number_format($row, 0, ',', '.') . " linhas.", $itemsAdded, 0);
            }
        }

        // Flush remaining
        if (count($materialsBatch) > 0) {
            CatalogItem::insert($materialsBatch);
        }

        $this->logSuccess("Importação concluída! Total de novos materiais importados: " . number_format($itemsAdded, 0, ',', '.') . ". Taxonomias: " . $taxonomiesAdded);
        $this->updateProgress(100, "Importação de materiais finalizada!", $itemsAdded, 0, 'completed');
    }

    /**
     * Parse and import services from catser.xlsx
     */
    private function importServices($worksheet, $highestRow)
    {
        // Headers are on Row 3, Data starts on Row 4
        $startRow = 4;
        $batchSize = 250;
        
        $processed = 0;
        $taxonomiesAdded = 0;
        $servicesAdded = 0;

        $servicesBatch = [];

        // Cache existing items
        $existingServices = CatalogService::pluck('service_code')->flip()->toArray();
        $existingTaxonomies = GovCatalogTaxonomy::where('catalog_type', 'service')
            ->get(['level_name', 'code'])
            ->mapWithKeys(fn($item) => [$item->level_name . '_' . $item->code => true])
            ->toArray();

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $groupCode   = $this->cleanString($worksheet->getCell("B$row")->getValue());
            $groupName   = $this->cleanString($worksheet->getCell("C$row")->getValue());
            $classCode   = $this->cleanString($worksheet->getCell("D$row")->getValue());
            $className   = $this->cleanString($worksheet->getCell("E$row")->getValue());
            $serviceCode = $this->cleanString($worksheet->getCell("F$row")->getValue());
            $description = $this->cleanString($worksheet->getCell("G$row")->getValue());
            $statusStr   = $this->cleanString($worksheet->getCell("H$row")->getValue());

            if (empty($serviceCode) || empty($description)) {
                continue;
            }

            // Map Taxonomy Nodes safely
            if (!empty($groupCode) && !empty($groupName) && !isset($existingTaxonomies['group_' . $groupCode])) {
                GovCatalogTaxonomy::updateOrCreate(
                    ['catalog_type' => 'service', 'level_name' => 'group', 'code' => $groupCode],
                    ['description' => $groupName]
                );
                $existingTaxonomies['group_' . $groupCode] = true;
                $taxonomiesAdded++;
            }

            if (!empty($classCode) && !empty($className) && !isset($existingTaxonomies['class_' . $classCode])) {
                GovCatalogTaxonomy::updateOrCreate(
                    ['catalog_type' => 'service', 'level_name' => 'class', 'code' => $classCode],
                    ['description' => $className, 'parent_code' => $groupCode]
                );
                $existingTaxonomies['class_' . $classCode] = true;
                $taxonomiesAdded++;
            }

            $isActive = (stripos($statusStr, 'Inativo') === false);

            $serviceData = [
                'service_code' => $serviceCode,
                'description' => $description,
                'group_code' => (int)$groupCode,
                'group_name' => $groupName,
                'is_active' => $isActive,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            // Add or update
            if (isset($existingServices[$serviceCode])) {
                CatalogService::where('service_code', $serviceCode)->update(array_intersect_key($serviceData, array_flip([
                    'description', 'group_code', 'group_name', 'is_active'
                ])));
            } else {
                $servicesBatch[] = $serviceData;
                $existingServices[$serviceCode] = true;
                $servicesAdded++;
            }

            $processed++;

            // Flush batches
            if (count($servicesBatch) >= $batchSize) {
                CatalogService::insert($servicesBatch);
                $servicesBatch = [];

                $pct = min(100, round(($row / $highestRow) * 100));
                $this->logInfo("[CATSER] Processadas " . number_format($row, 0, ',', '.') . " de " . number_format($highestRow, 0, ',', '.') . " linhas...");
                $this->updateProgress($pct, "Importando serviços (CATSER)... Processadas " . number_format($row, 0, ',', '.') . " linhas.", 0, $servicesAdded);
            }
        }

        // Flush remaining
        if (count($servicesBatch) > 0) {
            CatalogService::insert($servicesBatch);
        }

        $this->logSuccess("Importação concluída! Total de novos serviços importados: " . number_format($servicesAdded, 0, ',', '.') . ". Taxonomias: " . $taxonomiesAdded);
        $this->updateProgress(100, "Importação de serviços finalizada!", 0, $servicesAdded, 'completed');
    }

    /**
     * Clean and strip values from Excel cells safely
     */
    private function cleanString($val)
    {
        if ($val === null) return '';
        $cleaned = trim((string)$val);
        // Remove leading single quotes often used to force string type in Excel
        if (str_starts_with($cleaned, "'")) {
            $cleaned = substr($cleaned, 1);
        }
        return $cleaned;
    }

    /**
     * Log and cache management
     */
    private function logInfo($msg)
    {
        $this->info($msg);
        $this->writeToCacheLog($msg);
    }

    private function logSuccess($msg)
    {
        $this->comment($msg);
        $this->writeToCacheLog("[SUCCESS] " . $msg);
    }

    private function logError($msg)
    {
        $this->error($msg);
        $this->writeToCacheLog("[ERROR] " . $msg);
    }

    private function writeToCacheLog($msg)
    {
        $progress = Cache::get('etl_progress') ?: $this->getDefaultProgress();
        $logs = $progress['logs'] ?? [];
        $logs[] = '[' . date('H:i:s') . '] ' . $msg;
        
        // Keep logs buffer safe (limit to last 150 rows)
        if (count($logs) > 150) {
            array_shift($logs);
        }
        
        $progress['logs'] = $logs;
        Cache::put('etl_progress', $progress, 3600);
    }

    private function updateProgress($percentage, $message, $mCount = 0, $sCount = 0, $status = 'processing')
    {
        $progress = Cache::get('etl_progress') ?: $this->getDefaultProgress();
        
        $progress['status'] = $status;
        $progress['progress'] = $percentage;
        $progress['message'] = $message;
        
        if ($mCount > 0) {
            $progress['processed_materials'] = ($progress['processed_materials'] ?? 0) + $mCount;
        }
        if ($sCount > 0) {
            $progress['processed_services'] = ($progress['processed_services'] ?? 0) + $sCount;
        }

        // Live stats counters
        $progress['materials_count'] = CatalogItem::count();
        $progress['services_count'] = CatalogService::count();
        $progress['taxonomy_count'] = GovCatalogTaxonomy::count();

        Cache::put('etl_progress', $progress, 3600);
    }

    private function getDefaultProgress()
    {
        return [
            'status' => 'processing',
            'progress' => 0,
            'message' => 'Lendo arquivos...',
            'processed_materials' => 0,
            'processed_services' => 0,
            'current_page' => 1,
            'total_pages' => 1,
            'logs' => [],
            'materials_count' => CatalogItem::count(),
            'services_count' => CatalogService::count(),
            'taxonomy_count' => GovCatalogTaxonomy::count(),
        ];
    }
}
