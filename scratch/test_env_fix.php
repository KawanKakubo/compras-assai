<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Planning\ProcurementRequest;
use App\Services\Planning\DocumentTemplateService;

$request = ProcurementRequest::find(7); // Use the one from the error
if (!$request) {
    echo "Request 7 not found, using first.\n";
    $request = ProcurementRequest::first();
}

echo "Testing generation for Request: " . $request->reference_code . "\n";

$service = new DocumentTemplateService();

try {
    echo "Generating SD...\n";
    $sdPath = $service->generateSd($request);
    echo "SD generated at: $sdPath\n";

    echo "Generating ETP...\n";
    $etpPath = $service->generateEtp($request);
    echo "ETP generated at: $etpPath\n";
    echo "SUCCESS!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
