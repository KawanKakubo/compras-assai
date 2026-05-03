<?php

namespace App\Models\Planning {
    class ProcurementRequest {
        public $reference_code = 'SD-TEST-2026-001';
        public $secretaria = 'SECRETARIA DE SAÚDE';
        public $object_summary = 'Aquisição de medicamentos básicos para a rede municipal.';
        public $need_justification = 'Garantir o abastecimento das farmácias básicas e o atendimento à população.';
        public $priority_level = 'high';
        public $planned_conclusion_at;
        public $requester_name = 'João Silva';
        public $requester_cpf = '123.456.789-00';
        public $requester_role = 'Diretor de Saúde';
        public $responsible_name = 'Maria Santos';
        public $responsible_cpf = '987.654.321-11';
        public $responsible_role = 'Secretária de Saúde';
        public $linked_request = 'N/A';
        public $environmental_impacts = 'Não gera impactos.';
        public $reverse_logistics = 'Não se aplica.';
        public $municipal_policy_applies = true;
        public $municipal_policy_justification = 'Fomento ao comércio local.';
        public $requisition_unit = 'Unidade Básica de Saúde Central';
        public $title = 'Aquisição de Medicamentos';
        public $items;
        public $studies;

        public function __construct() {
            $this->planned_conclusion_at = new \DateTime('2026-12-31');
            $this->items = \collect([
                (object)[
                    'description' => 'Medicamento A',
                    'quantity' => 100,
                    'unit' => 'UN',
                    'unit_value' => 10.50,
                    'total_value' => 1050.00
                ]
            ]);
            $this->studies = \collect([
                (object)[
                    'need_description' => 'Necessidade detalhada do ETP.',
                    'motivation' => 'Motivação detalhada do ETP.',
                    'is_in_pca' => true,
                    'pca_reference' => 'PCA-2026-045',
                    'pca_demonstration' => 'Item 45 do PCA setorial.',
                    'prerequisites' => 'Nenhum.',
                    'linked_contracts' => 'Nenhum.',
                    'solution_requirements' => 'Certificação ANVISA.',
                    'environmental_impacts' => 'Baixo.',
                    'solution_survey' => 'Pesquisa realizada com 3 fornecedores.',
                    'unviable_solutions' => 'Nenhuma identificada.',
                    'splitting_justification' => 'Não parcelado.',
                    'solution_description' => 'Solução completa.',
                    'intended_results' => 'Melhora no atendimento.',
                    'viability_decision' => 'viable',
                    'viability_justification' => 'Tudo certo.',
                    'estimated_total_cost' => 1050.00,
                    'solution_mapping' => 'Mapa de soluções.',
                    'discarded_solutions' => 'Nenhuma.',
                    'chosen_solution' => 'Medicamento Genérico.',
                    'pca_description' => 'Descrição PCA'
                ]
            ]);
        }

        public function loadMissing($relations) { return $this; }
    }
}

namespace App\Services\Planning {
    function env($key, $default = null) {
        if ($key === 'PYTHON_PATH') return 'python3';
        return $default;
    }
    function config($key, $default = null) {
        return $default;
    }
}

namespace Illuminate\Support\Facades {
    class Log {
        public static function info($msg, $context = []) {
            echo "INFO: $msg " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
        public static function error($msg, $context = []) {
            echo "ERROR: $msg " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
    }

    class Process {
        public static function run($cmd) {
            $command = implode(' ', array_map('escapeshellarg', $cmd));
            exec($command . ' 2>&1', $output, $resultCode);
            return new class($output, $resultCode, $command) {
                public $output;
                public $resultCode;
                public $command;
                public function __construct($o, $r, $c) { $this->output = implode("\n", $o); $this->resultCode = $r; $this->command = $c; }
                public function output() { return $this->output; }
                public function errorOutput() { return $this->output; }
                public function failed() { return $this->resultCode !== 0; }
                public function command() { return $this->command; }
            };
        }
    }
}

namespace {
    // Mock functions
    function base_path($path = '') {
        return '/home/kawan/Documents/code/areas/SECTI/compras-assai/' . $path;
    }

    function storage_path($path = '') {
        return '/home/kawan/Documents/code/areas/SECTI/compras-assai/storage/' . $path;
    }

    function collect($arr) {
        return new class($arr) {
            public $items;
            public function __construct($i) { $this->items = $i; }
            public function map($fn) { return collect(array_map($fn, $this->items)); }
            public function toArray() { return $this->items; }
            public function sum($keyOrFn) {
                $sum = 0;
                foreach ($this->items as $i) {
                    if (is_callable($keyOrFn)) $sum += $keyOrFn($i);
                    else $sum += $i->$keyOrFn;
                }
                return $sum;
            }
            public function first() { return $this->items[0] ?? null; }
            public function isEmpty() { return empty($this->items); }
            public function where($k, $v) { return $this; }
            public function isNotEmpty() { return !empty($this->items); }
        };
    }

    require_once base_path('app/Services/Planning/DocumentTemplateService.php');

    use App\Services\Planning\DocumentTemplateService;
    use App\Models\Planning\ProcurementRequest;

    $service = new DocumentTemplateService();
    $request = new ProcurementRequest();

    echo "Testing SD generation...\n";
    try {
        $sdPath = $service->generateSd($request);
        echo "SD generated at: $sdPath\n";
    } catch (Exception $e) {
        echo "SD Failed: " . $e->getMessage() . "\n";
    }

    echo "\nTesting ETP generation...\n";
    try {
        $etpPath = $service->generateEtp($request);
        echo "ETP generated at: $etpPath\n";
    } catch (Exception $e) {
        echo "ETP Failed: " . $e->getMessage() . "\n";
    }
}
