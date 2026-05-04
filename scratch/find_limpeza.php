<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ComprasGov\ServiceCatalogService;

$service = app(ServiceCatalogService::class);
echo "Scanning Sections (Case Insensitive)...\n";
$sections = $service->getSections();
foreach($sections['resultado'] ?? [] as $s) {
    echo "- Section " . $s['codigo'] . ": " . $s['descricao'] . "\n";
}

echo "\nSearching for Limpeza in Divisions...\n";
// Section 8 (Support services) often has Limpeza
$divisions = $service->getDivisions(8);
foreach($divisions['resultado'] ?? [] as $d) {
    echo "- Division " . $d['codigo'] . ": " . $d['descricao'] . "\n";
}

echo "\nSearching for Limpeza in Divisions of Section 9...\n";
$divisions9 = $service->getDivisions(9);
foreach($divisions9['resultado'] ?? [] as $d) {
    echo "- Division " . $d['codigo'] . ": " . $d['descricao'] . "\n";
}
