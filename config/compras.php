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
];
