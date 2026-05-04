<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ComprasGov\ComprasGovApiClient;

$client = app(ComprasGovApiClient::class);
echo "Testing Subclasses WITHOUT filter to see if Gov API is alive...\n";

// Try without filter
$result = $client->getTaxonomy('/modulo-servico/5_consultarSubClasseServico', ['pagina' => 1]);

if (isset($result['error'])) {
    echo "ERROR WITHOUT FILTER: " . $result['error'] . "\n";
} else {
    echo "SUCCESS WITHOUT FILTER! Found " . count($result['resultado'] ?? []) . " subclasses.\n";
}

echo "\nTesting Subclasses WITH filter (Class 5429)...\n";
$result2 = $client->getTaxonomy('/modulo-servico/5_consultarSubClasseServico', ['codigoClasse' => 5429]);

if (isset($result2['error'])) {
    echo "ERROR WITH FILTER: " . $result2['error'] . "\n";
} else {
    echo "SUCCESS WITH FILTER! Found " . count($result2['resultado'] ?? []) . " subclasses.\n";
}
