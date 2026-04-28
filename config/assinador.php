<?php

return [
    'base_url' => env('ASSINADOR_BASE_URL', 'https://assinador.assai.pr.gov.br/api'),
    'timeout' => (int) env('ASSINADOR_TIMEOUT', 30),
    'connect_timeout' => (int) env('ASSINADOR_CONNECT_TIMEOUT', 10),
    
    // IdP Gov.Assaí Integration
    'gov_idp_url' => env('GOV_ASSAI_URL', 'https://gov.assai.pr.gov.br'),
    
    // Security JWT (RS256) - Paths relative to gov project (or we can copy keys)
    // For now, we assume this app can access the gov directory if it's on the same server,
    // but better to have its own config or a shared storage.
    'public_key_path' => env('GOV_ASSAI_PUBLIC_KEY_PATH', '../gov/storage/app/keys/gov_assai_public.pem'),
];
