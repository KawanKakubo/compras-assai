<?php

use App\Http\Controllers\Api\ComprasGovLookupController;
use App\Http\Controllers\Planning\ModuleOneController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/planejamento/modulo-1', [ModuleOneController::class, 'create'])->name('planning.module-one.create');
Route::post('/planejamento/modulo-1', [ModuleOneController::class, 'store'])->name('planning.module-one.store');
Route::get('/planejamento/modulo-1/{procurementRequest}', [ModuleOneController::class, 'show'])->name('planning.module-one.show');

Route::prefix('api/compras-gov')->controller(ComprasGovLookupController::class)->group(function (): void {
    Route::get('/material/items', 'materialItems');
    Route::get('/material/units', 'materialUnits');
    Route::get('/material/characteristics', 'materialCharacteristics');
    Route::get('/service/items', 'serviceItems');
    Route::get('/service/units', 'serviceUnits');
    Route::get('/material/prices', 'materialPrices');
    Route::get('/service/prices', 'servicePrices');
    Route::get('/uasg', 'uasg');
});
