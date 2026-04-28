<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CatalogItem;
use App\Models\CatalogService;

class SyncCatalogCommand extends Command
{
    protected $signature = 'catalog:sync {--bootstrap : Popular com dados base detalhados}';
    protected $description = 'Sincroniza o catálogo local de materiais e serviços (CATMAT/CATSER)';

    public function handle()
    {
        if ($this->option('bootstrap')) {
            $this->info('Populando banco de dados com dados base detalhados...');
            $this->bootstrapData();
            $this->info('Bootstrap concluído com sucesso!');
        }

        $this->info('Busca detalhada finalizada.');
    }

    private function bootstrapData()
    {
        $materials = [
            // Papelaria e Escritório
            ['item_code' => 150032, 'pdm_code' => 17351, 'pdm_name' => 'PAPEL SULFITE', 'class_code' => 7530, 'class_name' => 'ARTIGOS DE PAPELARIA', 'group_code' => 75, 'group_name' => 'MATERIAL DE ESCRITÓRIO', 'description' => 'PAPEL SULFITE, TIPO: ALCALINO, COR: BRANCO, FORMATO: A4, GRAMATURA: 75 G/M2', 'is_sustainable' => true, 'search_aliases' => 'papel a4, sulfite, resma'],
            ['item_code' => 334120, 'pdm_code' => 1845, 'pdm_name' => 'CANETA ESFEROGRÁFICA', 'class_code' => 7520, 'class_name' => 'ACESSÓRIOS DE ESCRITÓRIO', 'group_code' => 75, 'group_name' => 'MATERIAL DE ESCRITÓRIO', 'description' => 'CANETA ESFEROGRÁFICA, COR TINTA: AZUL, TIPO: CORPO PLÁSTICO TRANSPARENTE, PONTA MÉDIA 1.0MM', 'search_aliases' => 'caneta bic, caneta azul, escrita'],
            ['item_code' => 245110, 'pdm_code' => 1234, 'pdm_name' => 'LÁPIS PRETO', 'class_code' => 7520, 'class_name' => 'ACESSÓRIOS DE ESCRITÓRIO', 'group_code' => 75, 'group_name' => 'MATERIAL DE ESCRITÓRIO', 'description' => 'LÁPIS PRETO, GRAU DUREZA: HB, MATERIAL: MADEIRA REFLORESTADA', 'search_aliases' => 'lápis, grafite, escrita'],
            
            // Informática
            ['item_code' => 441029, 'pdm_code' => 411, 'pdm_name' => 'MICROCOMPUTADOR', 'class_code' => 7010, 'class_name' => 'EQUIPAMENTOS DE TI', 'group_code' => 70, 'group_name' => 'INFORMÁTICA', 'description' => 'MICROCOMPUTADOR DESKTOP, PROCESSADOR: CORE I7, RAM: 16GB, SSD: 512GB, MONITOR 23.8 POL', 'search_aliases' => 'computador, pc, desktop, i7'],
            ['item_code' => 482110, 'pdm_code' => 412, 'pdm_name' => 'NOTEBOOK', 'class_code' => 7010, 'class_name' => 'EQUIPAMENTOS DE TI', 'group_code' => 70, 'group_name' => 'INFORMÁTICA', 'description' => 'NOTEBOOK PROFISSIONAL, TELA: 15.6 POL, PROCESSADOR: CORE I5, RAM: 8GB, SSD: 256GB', 'search_aliases' => 'lap-top, notebook, i5, ryzen'],
            ['item_code' => 311204, 'pdm_code' => 505, 'pdm_name' => 'IMPRESSORA MULTIFUNCIONAL', 'class_code' => 7025, 'class_name' => 'PERIFÉRICO DE TI', 'group_code' => 70, 'group_name' => 'INFORMÁTICA', 'description' => 'IMPRESSORA MULTIFUNCIONAL LASER, TIPO: MONOCROMÁTICA, VELOCIDADE: 40 PPM, CONEXÃO: REDE E WIFI', 'search_aliases' => 'impressora, xerox, cópia, laser'],
            
            // Mobiliário
            ['item_code' => 206504, 'pdm_code' => 313, 'pdm_name' => 'CADEIRA ESCRITÓRIO', 'class_code' => 7110, 'class_name' => 'MOBILIÁRIO ESCRITÓRIO', 'group_code' => 71, 'group_name' => 'MOBILIÁRIOS', 'description' => 'CADEIRA ESCRITÓRIO GIRATÓRIA, COM BRAÇOS, REGULAGEM DE ALTURA, REVESTIMENTO EM TECIDO PRETO', 'search_aliases' => 'cadeira, giratória, móvel'],
            ['item_code' => 243756, 'pdm_code' => 314, 'pdm_name' => 'MESA DE TRABALHO', 'class_code' => 7110, 'class_name' => 'MOBILIÁRIO ESCRITÓRIO', 'group_code' => 71, 'group_name' => 'MOBILIÁRIOS', 'description' => 'MESA DE TRABALHO EM L, MATERIAL: MDP 25MM, DIMENSÕES: 1600 X 1400 MM', 'search_aliases' => 'mesa, escrivaninha, móvel'],
            
            // Saúde / Medicamentos
            ['item_code' => 456789, 'pdm_code' => 2001, 'pdm_name' => 'DIPIRONA SÓDICA', 'class_code' => 6505, 'class_name' => 'DROGAS E MEDICAMENTOS', 'group_code' => 65, 'group_name' => 'SAÚDE', 'description' => 'DIPIRONA SÓDICA, DOSAGEM: 500 MG/ML, FORMA FARMACÊUTICA: SOLUÇÃO INJETÁVEL', 'search_aliases' => 'remédio, febre, dor, dipirona'],
            ['item_code' => 456790, 'pdm_code' => 2002, 'pdm_name' => 'PARACETAMOL', 'class_code' => 6505, 'class_name' => 'DROGAS E MEDICAMENTOS', 'group_code' => 65, 'group_name' => 'SAÚDE', 'description' => 'PARACETAMOL, DOSAGEM: 500 MG, FORMA FARMACÊUTICA: COMPRIMIDO', 'search_aliases' => 'remédio, febre, dor, tylenol'],
            
            // Veículos (Novos!)
            ['item_code' => 900101, 'pdm_code' => 2954, 'pdm_name' => 'VEÍCULO DE REPRESENTAÇÃO', 'class_code' => 2310, 'class_name' => 'VEÍCULOS AUTOMOTORES', 'group_code' => 23, 'group_name' => 'VEÍCULOS', 'description' => 'VEÍCULO AUTOMOTOR DE PASSEIO, MOTOR MÍNIMO 1.0, 4 PORTAS, AR CONDICIONADO, DIREÇÃO HIDRÁULICA', 'search_aliases' => 'carro, fiat, uno, volkswagen, gol, veículo leve'],
            
            // Construção / Materiais
            ['item_code' => 500101, 'pdm_code' => 3001, 'pdm_name' => 'CIMENTO PORTLAND', 'class_code' => 5610, 'class_name' => 'MATERIAIS DE CONSTRUÇÃO', 'group_code' => 56, 'group_name' => 'CONSTRUÇÃO', 'description' => 'CIMENTO PORTLAND CP-II-Z-32, EMBALAGEM: SACO 50KG'],
            ['item_code' => 500102, 'pdm_code' => 3002, 'pdm_name' => 'AREIA MÉDIA', 'class_code' => 5610, 'class_name' => 'MATERIAIS DE CONSTRUÇÃO', 'group_code' => 56, 'group_name' => 'CONSTRUÇÃO', 'description' => 'AREIA MÉDIA LAVADA, PARA CONSTRUÇÃO CIVIL'],
            
            // Alimentação / Copa
            ['item_code' => 600101, 'pdm_code' => 4001, 'pdm_name' => 'CAFÉ EM PÓ', 'class_code' => 8955, 'class_name' => 'CAFÉ, CHÁ E CONDIMENTOS', 'group_code' => 89, 'group_name' => 'ALIMENTOS', 'description' => 'CAFÉ EM PÓ, TIPO: TORRADO E MOÍDO, EMBALAGEM: VÁCUO 500G'],
            ['item_code' => 600102, 'pdm_code' => 4002, 'pdm_name' => 'AÇÚCAR REFINADO', 'class_code' => 8925, 'class_name' => 'AÇÚCAR E XAROPE', 'group_code' => 89, 'group_name' => 'ALIMENTOS', 'description' => 'AÇÚCAR REFINADO AMORFO, EMBALAGEM: SACO 1KG'],
            
            // Eletrodomésticos
            ['item_code' => 700101, 'pdm_code' => 5001, 'pdm_name' => 'AR CONDICIONADO', 'class_code' => 4120, 'class_name' => 'EQUIPAMENTOS DE AR CONDICIONADO', 'group_code' => 41, 'group_name' => 'CLIMATIZAÇÃO', 'description' => 'AR CONDICIONADO SPLIT, CAPACIDADE: 12000 BTUS, CICLO: FRIO, TECNOLOGIA: INVERTER'],
            ['item_code' => 700102, 'pdm_code' => 5002, 'pdm_name' => 'FRIGOBAR', 'class_code' => 4110, 'class_name' => 'EQUIPAMENTOS DE REFRIGERAÇÃO', 'group_code' => 41, 'group_name' => 'CLIMATIZAÇÃO', 'description' => 'FRIGOBAR 120 LITROS, COR: BRANCO, VOLTAGEM: 110V/220V'],
            
            // Ferramentas
            ['item_code' => 800101, 'pdm_code' => 6001, 'pdm_name' => 'FURADEIRA DE IMPACTO', 'class_code' => 5130, 'class_name' => 'FERRAMENTAS ELÉTRICAS', 'group_code' => 51, 'group_name' => 'FERRAMENTAL', 'description' => 'FURADEIRA DE IMPACTO PROFISSIONAL, POTÊNCIA: 700W, MANDRIL: 1/2 POL'],
        ];

        foreach ($materials as $m) {
            CatalogItem::updateOrCreate(['item_code' => $m['item_code']], $m);
        }

        $services = [
            ['service_code' => 1010, 'group_code' => 1, 'group_name' => 'MANUTENÇÃO', 'description' => 'SERVIÇO DE MANUTENÇÃO PREVENTIVA E CORRETIVA DE AR CONDICIONADO'],
            ['service_code' => 2020, 'group_code' => 2, 'group_name' => 'LIMPEZA', 'description' => 'SERVIÇO DE LIMPEZA E CONSERVAÇÃO PREDIAL, INCLUINDO MATERIAIS E EQUIPAMENTOS'],
            ['service_code' => 3030, 'group_code' => 3, 'group_name' => 'VIGILÂNCIA', 'description' => 'SERVIÇO DE VIGILÂNCIA ARMADA 24 HORAS'],
            ['service_code' => 4040, 'group_code' => 4, 'group_name' => 'TRANSPORTE', 'description' => 'SERVIÇO DE TRANSPORTE ESCOLAR MUNICIPAL'],
        ];

        foreach ($services as $s) {
            CatalogService::updateOrCreate(['service_code' => $s['service_code']], $s);
        }
    }
}
