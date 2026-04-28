<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Planning\ModuleOneController;
use App\Http\Controllers\Api\ComprasGovLookupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboards
Route::middleware(['auth'])->group(function () {
    
    // Admin
    Route::middleware(['role:admin'])->prefix('admin')->controller(\App\Http\Controllers\Admin\AdminController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('admin.dashboard');
        Route::get('/users', 'indexUsers')->name('admin.users.index');
        Route::post('/users', 'storeUser')->name('admin.users.store');
        Route::get('/users/{user}/edit', 'editUser')->name('admin.users.edit');
        Route::put('/users/{user}', 'updateUser')->name('admin.users.update');
        Route::delete('/users/{user}', 'destroyUser')->name('admin.users.destroy');
    });

    // Secretaria
    Route::middleware(['role:secretaria'])->prefix('secretaria')->controller(\App\Http\Controllers\Secretaria\DashboardController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('secretaria.dashboard');
        
        // Intelligent Form (Module 1)
        Route::get('/planejamento/modulo-1', [ModuleOneController::class, 'create'])->name('planning.module-one.create');
        Route::post('/planejamento/modulo-1', [ModuleOneController::class, 'store'])->name('planning.module-one.store');
    });

    // Gabinete
    Route::middleware(['role:gabinete'])->prefix('gabinete')->controller(\App\Http\Controllers\Gabinete\DashboardController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('gabinete.dashboard');
        Route::post('/request/{id}/approve', 'approve')->name('gabinete.approve');
        Route::post('/request/{id}/deny', 'deny')->name('gabinete.deny');
    });

    // Compras
    Route::middleware(['role:compras'])->prefix('compras')->controller(\App\Http\Controllers\Compras\DashboardController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('compras.dashboard');
        Route::post('/request/{id}/finalize', 'finalize')->name('compras.finalize');
    });

    // Shared Procurement Routes (Show/Download)
    Route::get('/planejamento/modulo-1/{procurementRequest}', [ModuleOneController::class, 'show'])->name('planning.module-one.show');
    Route::get('/planejamento/modulo-1/{procurementRequest}/sd', [ModuleOneController::class, 'downloadSd'])->name('planning.module-one.download-sd');
    Route::get('/planejamento/modulo-1/{procurementRequest}/etp', [ModuleOneController::class, 'downloadEtp'])->name('planning.module-one.download-etp');
    Route::get('/planejamento/modulo-1/{procurementRequest}/tr', [ModuleOneController::class, 'downloadTr'])->name('planning.module-one.download-tr');

    // Digital Signature
    Route::post('/planejamento/modulo-1/{procurementRequest}/signature/request-mfa', [\App\Http\Controllers\Planning\SignatureController::class, 'requestMfa'])->name('planning.signature.request-mfa');
    Route::post('/planejamento/modulo-1/{procurementRequest}/signature/sign', [\App\Http\Controllers\Planning\SignatureController::class, 'sign'])->name('planning.signature.sign');
});

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
