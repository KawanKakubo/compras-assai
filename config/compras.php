<?php

return [
    'api_base_url' => env('COMPRAS_GOV_API_BASE_URL', 'https://dadosabertos.compras.gov.br'),

    'lei_14133' => [
        'referencia' => env('LEI_14133_REFERENCIA', 'Decreto 12.807/2025'),
        'vigencia' => env('LEI_14133_VIGENCIA', '2026-01-01'),
        'dispensa' => [
            'art75' => [
                'inciso_i' => (float) env('LEI_14133_ART75_INCISO_I', 130984.20),
                'inciso_ii' => (float) env('LEI_14133_ART75_INCISO_II', 65492.11),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Secretarias Municipais
    |--------------------------------------------------------------------------
    */
    'secretarias' => [
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Programa Municipal de Compras
    |--------------------------------------------------------------------------
    */
    'programa_municipal' => [
        'lei' => env('LEI_MUNICIPAL_COMPRAS', 'Lei Municipal nº ____/2026'),
        'programa' => env('PROGRAMA_COMPRAS', 'Programa Compras Assaí'),
        'limite_exclusividade_me_epp' => 80000.00,
        'percentual_cota_reservada' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prioridades
    |--------------------------------------------------------------------------
    */
    'prioridades' => [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unidades de Medida Comuns (fallback quando API não retorna)
    |--------------------------------------------------------------------------
    */
    'unidades_comuns' => [
        'UN' => 'Unidade',
        'KG' => 'Quilograma',
        'LT' => 'Litro',
        'MT' => 'Metro',
        'M2' => 'Metro Quadrado',
        'M3' => 'Metro Cúbico',
        'CX' => 'Caixa',
        'PC' => 'Pacote',
        'RL' => 'Rolo',
        'RS' => 'Resma',
        'FR' => 'Frasco',
        'GL' => 'Galão',
        'TB' => 'Tubo',
        'FL' => 'Folha',
        'JG' => 'Jogo',
        'KT' => 'Kit',
        'MH' => 'Mês/Homem',
        'SV' => 'Serviço',
        'HR' => 'Hora',
        'DI' => 'Diária',
    ],
];
