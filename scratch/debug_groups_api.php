<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ComprasGov\ServiceCatalogService;

$service = app(ServiceCatalogService::class);
echo "Syncing Service Groups for Division 54 through Service...\n";

// This will trigger getOrSyncTaxonomy and save to GovCatalogTaxonomy table
$result = $service->getGroups(54);

if (isset($result['error'])) {
    echo "ERROR: " . $result['error'] . "\n";
} else {
    echo "SUCCESS! " . count($result['resultado'] ?? []) . " groups synced to local database.\n";
    foreach($result['resultado'] as $item) {
        echo "- " . $item['codigoGrupo'] . ": " . $item['nomeGrupo'] . " [Source: " . ($result['source'] ?? 'unknown') . "]\n";
    }
}
