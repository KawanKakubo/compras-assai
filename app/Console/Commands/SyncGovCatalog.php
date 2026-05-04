<?php

namespace App\Console\Commands;

use App\Services\ComprasGov\MaterialCatalogService;
use App\Services\ComprasGov\ServiceCatalogService;
use Illuminate\Console\Command;

class SyncGovCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compras:sync-catalog 
                            {--type=all : O que sincronizar (material, service, all)} 
                            {--level=2 : Profundidade da taxonomia (1=Grupos, 2=Classes, 3=PDMs/Subclasses)}
                            {--with-items : Se deve sincronizar também os itens (folhas) - aviso: lento para materiais}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza uma varredura completa no catálogo do Governo e armazena localmente para evitar erros de rede.';

    /**
     * Execute the console command.
     */
    public function handle(MaterialCatalogService $materialService, ServiceCatalogService $serviceService)
    {
        $type = $this->option('type');
        $level = (int)$this->option('level');
        $withItems = $this->option('with-items');

        $this->info("Iniciando sincronização do catálogo...");

        if ($type === 'all' || $type === 'service') {
            $this->syncServices($serviceService, $level, $withItems);
        }

        if ($type === 'all' || $type === 'material') {
            $this->syncMaterials($materialService, $level, $withItems);
        }

        $this->info("Sincronização concluída com sucesso!");
    }

    private function syncServices(ServiceCatalogService $service, int $maxLevel, bool $withItems)
    {
        $this->comment("Sincronizando Serviços (CATSER)...");
        
        $sections = $service->getSections();
        if (empty($sections['resultado'])) return;

        $bar = $this->output->createProgressBar(count($sections['resultado']));
        $bar->start();

        foreach ($sections['resultado'] as $section) {
            $sectionCode = $section['codigoSecao'] ?? $section['codigo'];
            $this->comment(" -> Sincronizando Seção $sectionCode...");
            
            try {
                if ($maxLevel >= 2) {
                    $divisions = $service->getDivisions($sectionCode);
                    foreach ($divisions['resultado'] ?? [] as $division) {
                        $divCode = $division['codigoDivisao'] ?? $division['codigo'];
                        
                        try {
                            if ($maxLevel >= 3) {
                                $groups = $service->getGroups((int)$divCode);
                                foreach ($groups['resultado'] ?? [] as $group) {
                                    $groupCode = $group['codigoGrupo'] ?? $group['codigo'];
                                    $subclasses = $service->getClasses((int)$groupCode); // No CATSER, o nível 4 é Classe/Subclasse
                                    
                                    if ($withItems && !empty($subclasses['resultado'])) {
                                        foreach ($subclasses['resultado'] as $sub) {
                                            $subCode = $sub['codigoClasse'] ?? $sub['codigo'];
                                            $service->searchItems(['codigoSubclasse' => $subCode]);
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $this->warn("    ! Falha no Grupo/Classe da Divisão $divCode: " . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn("  ! Falha na Seção $sectionCode: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nServiços sincronizados até o nível $maxLevel.");
    }

    private function syncMaterials(MaterialCatalogService $material, int $maxLevel, bool $withItems)
    {
        $this->comment("Sincronizando Materiais (CATMAT)...");
        
        $groups = $material->getGroups();
        if (empty($groups['resultado'])) return;

        $bar = $this->output->createProgressBar(count($groups['resultado']));
        $bar->start();

        foreach ($groups['resultado'] as $group) {
            $groupCode = $group['codigoGrupo'] ?? $group['codigo'];
            
            if ($maxLevel >= 2) {
                $classes = $material->getClasses((int)$groupCode);
                
                if ($maxLevel >= 3) {
                    foreach ($classes['resultado'] ?? [] as $class) {
                        $classCode = $class['codigoClasse'] ?? $class['codigo'];
                        $pdms = $material->getPdms((int)$classCode); // Sync PDMs
                        
                        if ($withItems && !empty($pdms['resultado'])) {
                            foreach ($pdms['resultado'] as $pdm) {
                                $pdmCode = $pdm['codigoPdm'] ?? $pdm['codigo'];
                                $material->searchItems(['codigoPdm' => $pdmCode]);
                            }
                        }
                    }
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nMateriais sincronizados até o nível $maxLevel.");
    }
}
