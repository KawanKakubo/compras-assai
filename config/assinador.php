<?php

return [
    'base_url' => env('ASSINADOR_BASE_URL', 'https://assinador.assai.pr.gov.br/api'),
    'timeout' => (int) env('ASSINADOR_TIMEOUT', 60),
    'connect_timeout' => (int) env('ASSINADOR_CONNECT_TIMEOUT', 20),
    
    // IdP Gov.Assaí Integration
    'gov_idp_url' => env('GOV_ASSAI_URL', 'https://gov.assai.pr.gov.br'),
    
    // Security JWT (RS256) - Paths relative to gov project (or we can copy keys)
    'public_key_path' => env('GOV_ASSAI_PUBLIC_KEY_PATH', '../gov/storage/app/keys/gov_assai_public.pem'),

    // Modo de teste: ignora MFA e assinatura real (apenas para local/dev)
    'bypass' => env('ASSINADOR_BYPASS', false),
];
