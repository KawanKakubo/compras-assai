<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\ComprasGov\GovCatalogTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CacheGeometricoController extends Controller
{
    /**
     * Display the geometric/catalog caching dashboard.
     */
    public function index()
    {
        $materialsCount = CatalogItem::count();
        $servicesCount = CatalogService::count();
        $taxonomyCount = GovCatalogTaxonomy::count();

        // Let's get some sample items to show in a preview table
        $sampleItems = CatalogItem::latest()->take(5)->get();
        $sampleServices = CatalogService::latest()->take(5)->get();

        return view('admin.cache-geometrico.index', compact(
            'materialsCount',
            'servicesCount',
            'taxonomyCount',
            'sampleItems',
            'sampleServices'
        ));
    }

    /**
     * Trigger the download/bootstrap of CATMAT/CATSER open data (ETL in background).
     */
    public function sync(Request $request)
    {
        try {
            $progress = \Illuminate\Support\Facades\Cache::get('etl_progress');
            if ($progress && ($progress['status'] ?? '') === 'processing') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'O processo de ETL já está em andamento.'
                ], 422);
            }

            // Clear previous progress state before firing background task
            \Illuminate\Support\Facades\Cache::put('etl_progress', [
                'status' => 'processing',
                'progress' => 0,
                'message' => 'Iniciando o ETL de extração massiva...',
                'processed_materials' => 0,
                'processed_services' => 0,
                'current_page' => 0,
                'total_pages' => 100,
                'logs' => ['[ETL] Solicitando inicialização do processo em background...']
            ]);

            $artisan = base_path('artisan');
            $command = "php " . escapeshellarg($artisan) . " catalog:sync-etl > /dev/null 2>&1 &";
            exec($command);

            return response()->json([
                'status' => 'success',
                'message' => 'ETL massivo do catálogo iniciado em segundo plano com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao iniciar ETL: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Falha ao iniciar sincronização: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current progress of the ETL process.
     */
    public function progress()
    {
        $progress = \Illuminate\Support\Facades\Cache::get('etl_progress', [
            'status' => 'idle',
            'progress' => 0,
            'message' => 'Nenhum processo em execução.',
            'processed_materials' => 0,
            'processed_services' => 0,
            'current_page' => 0,
            'total_pages' => 0,
            'logs' => []
        ]);

        // Dynamically add current DB counts for UI updates
        $progress['materials_count'] = CatalogItem::count();
        $progress['services_count'] = CatalogService::count();
        $progress['taxonomy_count'] = GovCatalogTaxonomy::count();

        return response()->json($progress);
    }

    /**
     * Clear the local cache for catalog and taxonomies.
     */
    public function clear()
    {
        try {
            CatalogItem::truncate();
            CatalogService::truncate();
            GovCatalogTaxonomy::whereIn('catalog_type', ['material', 'service'])->delete();

            // Clear progress state
            \Illuminate\Support\Facades\Cache::forget('etl_progress');

            return redirect()->route('admin.cache-geometrico.index')
                ->with('success', 'Cache local de materiais, serviços e taxonomias limpo com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('admin.cache-geometrico.index')
                ->with('error', 'Falha ao limpar cache: ' . $e->getMessage());
        }
    }

    /**
     * Upload an Excel file and trigger background processing.
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:51200', // max 50MB
                'type' => 'required|string|in:material,service'
            ]);

            $progress = \Illuminate\Support\Facades\Cache::get('etl_progress');
            if ($progress && ($progress['status'] ?? '') === 'processing') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Um processo de sincronização ou importação já está em andamento.'
                ], 422);
            }

            $file = $request->file('file');
            $type = $request->input('type');

            // Save temporarily in tmp_uploads
            $tmpPath = storage_path('app/tmp_uploads');
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0755, true);
            }

            $fileName = uniqid('upload_') . '.' . $file->getClientOriginalExtension();
            $file->move($tmpPath, $fileName);
            $fullFilePath = $tmpPath . '/' . $fileName;

            // Initialize progress structure in cache
            \Illuminate\Support\Facades\Cache::put('etl_progress', [
                'status' => 'processing',
                'progress' => 0,
                'message' => 'Lendo cabeçalhos e inicializando importação offline...',
                'processed_materials' => 0,
                'processed_services' => 0,
                'current_page' => 1,
                'total_pages' => 1,
                'logs' => ['[IMPORT] Upload concluído com sucesso. Iniciando parser em segundo plano...']
            ]);

            // Execute the Artisan command in background using exec
            $artisan = base_path('artisan');
            $command = "php " . escapeshellarg($artisan) . " catalog:import-xlsx " . escapeshellarg($fullFilePath) . " " . escapeshellarg($type) . " > /dev/null 2>&1 &";
            exec($command);

            return response()->json([
                'status' => 'success',
                'message' => 'Upload concluído e importação iniciada em segundo plano!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao realizar upload do arquivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
