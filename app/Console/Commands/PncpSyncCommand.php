<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ComprasGov\PncpService;
use App\Models\CatalogItem;
use App\Models\CatalogService;

class PncpSyncCommand extends Command
{
    protected $signature = 'pncp:sync {--date=20250101} {--pages=1}';
    protected $description = 'Minera itens de contratações do PNCP (2025 em diante) para o banco local';

    public function __construct(private PncpService $pncp)
    {
        parent::__construct();
    }

    public function handle()
    {
        $date = $this->option('date');
        $maxPages = (int) $this->option('pages');

        $this->info("Iniciando mineração PNCP a partir de {$date}...");

        for ($page = 1; $page <= $maxPages; $page++) {
            $this->comment("Processando página {$page} de contratações...");
            
            $data = $this->pncp->getContratacoes($date, $page);
            
            if (!$data || empty($data['data'])) {
                $this->warn("Nenhuma contratação encontrada ou erro na API.");
                break;
            }

            foreach ($data['data'] as $contratacao) {
                $cnpj = $contratacao['orgaoEntidade']['cnpj'];
                $ano = $contratacao['anoContratacao'];
                $sequencial = $contratacao['sequencialContratacao'];
                $orgaoNome = $contratacao['orgaoEntidade']['razãoSocial'];

                $this->line(" - Extraindo itens de: {$orgaoNome} ({$ano}/{$sequencial})");

                $itens = $this->pncp->getItensContratacao($cnpj, $ano, $sequencial);

                foreach ($itens as $item) {
                    $this->saveItem($item);
                }
            }
        }

        $this->info("Mineração concluída com sucesso!");
    }

    private function saveItem(array $item)
    {
        $tipo = $item['materialOuServico'] ?? 'M';
        $codigo = $item['materialOuServicoCodigo'] ?? null;
        $descricao = $item['descricao'] ?? null;

        if (!$codigo || !$descricao) return;

        if ($tipo === 'M') {
            CatalogItem::updateOrCreate(
                ['item_code' => $codigo],
                [
                    'description' => mb_strtoupper($descricao),
                    'pdm_name' => mb_strtoupper($item['materialOuServicoNome'] ?? 'ITEM PNCP'),
                    'class_name' => 'MINERADO PNCP',
                    'group_name' => 'MINERADO PNCP',
                    'is_active' => true
                ]
            );
        } else {
            CatalogService::updateOrCreate(
                ['service_code' => $codigo],
                [
                    'description' => mb_strtoupper($descricao),
                    'group_name' => 'MINERADO PNCP',
                    'is_active' => true
                ]
            );
        }
    }
}
