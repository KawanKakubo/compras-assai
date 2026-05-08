<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\CatalogService;
use App\Models\ComprasGov\GovCatalogTaxonomy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportCatalogXlsxTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user to authorize requests
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    /**
     * Test uploading invalid file types is rejected.
     */
    public function test_upload_requires_valid_excel_and_type()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cache-geometrico.upload'), [
                'file' => UploadedFile::fake()->create('invalid.txt', 100, 'text/plain'),
                'type' => 'material'
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * Test uploading a valid file type initializes cache and returns success.
     */
    public function test_upload_accepts_excel_and_dispatches_background_import()
    {
        Cache::forget('etl_progress');

        // Create a small valid xlsx file in memory
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Title');
        
        $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'catmat_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.cache-geometrico.upload'), [
                'file' => $uploadedFile,
                'type' => 'material'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Upload concluído e importação iniciada em segundo plano!'
        ]);

        $progress = Cache::get('etl_progress');
        $this->assertNotNull($progress);
        $this->assertEquals('processing', $progress['status']);
        $this->assertStringContainsString('Upload concluído com sucesso', $progress['logs'][0]);

        @unlink($tempPath);
    }

    /**
     * Test importing materials via Artisan command.
     */
    public function test_import_materials_artisan_command_populates_database_and_taxonomy()
    {
        // 1. Create a mock CATMAT Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Title Row (Row 1)
        $sheet->setCellValue('A1', 'Extração realizada em 21/11/2025');
        
        // Headers Row (Row 2)
        $sheet->setCellValue('A2', 'Código do Grupo');
        $sheet->setCellValue('B2', 'Nome do Grupo');
        $sheet->setCellValue('C2', 'Código da Classe');
        $sheet->setCellValue('D2', 'Nome da Classe');
        $sheet->setCellValue('E2', 'Código do PDM');
        $sheet->setCellValue('F2', 'Nome do PDM');
        $sheet->setCellValue('G2', 'Código do Item');
        $sheet->setCellValue('H2', 'Descrição do Item');

        // Data Rows (Row 3, 4)
        $sheet->setCellValue('A3', '10');
        $sheet->setCellValue('B3', 'ARMAMENTO');
        $sheet->setCellValue('C3', '1005');
        $sheet->setCellValue('D3', 'ARMAS DE FOGO');
        $sheet->setCellValue('E3', '1712');
        $sheet->setCellValue('F3', 'PEÇAS DE ARMAMENTO');
        $sheet->setCellValue('G3', '446820');
        $sheet->setCellValue('H3', 'Micro Eixo de Metal para Pistola');

        $sheet->setCellValue('A4', '20');
        $sheet->setCellValue('B4', 'VESTUARIO');
        $sheet->setCellValue('C4', '2010');
        $sheet->setCellValue('D4', 'CALCADOS');
        $sheet->setCellValue('E4', '2520');
        $sheet->setCellValue('F4', 'BOTAS DE COURO');
        $sheet->setCellValue('G4', '556123');
        $sheet->setCellValue('H4', 'Bota de Segurança de Couro Preta');

        $tempPath = tempnam(sys_get_temp_dir(), 'catmat_test_import_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // 2. Call the artisan command
        $exitCode = Artisan::call('catalog:import-xlsx', [
            'filePath' => $tempPath,
            'type' => 'material'
        ]);

        $this->assertEquals(0, $exitCode);

        // 3. Assert Items are imported
        $item1 = CatalogItem::where('item_code', '446820')->first();
        $this->assertNotNull($item1);
        $this->assertEquals('Micro Eixo de Metal para Pistola', $item1->description);
        $this->assertEquals(10, $item1->group_code);
        $this->assertEquals('ARMAMENTO', $item1->group_name);
        $this->assertEquals(1005, $item1->class_code);
        $this->assertEquals('ARMAS DE FOGO', $item1->class_name);
        $this->assertEquals(1712, $item1->pdm_code);
        $this->assertEquals('PEÇAS DE ARMAMENTO', $item1->pdm_name);

        // 4. Assert Taxonomy Nodes are created
        $groupTax = GovCatalogTaxonomy::where('level_name', 'group')->where('code', '10')->first();
        $this->assertNotNull($groupTax);
        $this->assertEquals('ARMAMENTO', $groupTax->description);
        $this->assertEquals('material', $groupTax->catalog_type);

        $classTax = GovCatalogTaxonomy::where('level_name', 'class')->where('code', '1005')->first();
        $this->assertNotNull($classTax);
        $this->assertEquals('ARMAS DE FOGO', $classTax->description);
        $this->assertEquals('10', $classTax->parent_code);

        $pdmTax = GovCatalogTaxonomy::where('level_name', 'pdm')->where('code', '1712')->first();
        $this->assertNotNull($pdmTax);
        $this->assertEquals('PEÇAS DE ARMAMENTO', $pdmTax->description);
        $this->assertEquals('1005', $pdmTax->parent_code);

        // 5. Assert Progress State was updated to completed
        $progress = Cache::get('etl_progress');
        $this->assertEquals('completed', $progress['status']);
        $this->assertEquals(100, $progress['progress']);
        $this->assertStringContainsString('Importação de materiais finalizada', $progress['message']);

        @unlink($tempPath);
    }

    /**
     * Test importing services via Artisan command.
     */
    public function test_import_services_artisan_command_populates_database_and_taxonomy()
    {
        // 1. Create a mock CATSER Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Title Row (Row 1)
        $sheet->setCellValue('A1', 'Lista CATSER');
        // Info Row (Row 2)
        $sheet->setCellValue('A2', 'Extração realizada em 23/04/2025');
        
        // Headers Row (Row 3)
        $sheet->setCellValue('A3', 'Tipo Material Serviço');
        $sheet->setCellValue('B3', 'Grupo Serviço');
        $sheet->setCellValue('C3', 'Nome do Grupo');
        $sheet->setCellValue('D3', 'Classe Material');
        $sheet->setCellValue('E3', 'Nome da Classe');
        $sheet->setCellValue('F3', 'Codigo Material Serviço');
        $sheet->setCellValue('G3', 'Descrição do Serviço');
        $sheet->setCellValue('H3', 'Sit Atual Mat Serv');

        // Data Rows (Row 4, 5)
        $sheet->setCellValue('A4', 'Serviço');
        $sheet->setCellValue('B4', "'111"); // Test quote cleanup
        $sheet->setCellValue('C4', 'DESENVOLVIMENTO DE SOFTWARE');
        $sheet->setCellValue('D4', '1111');
        $sheet->setCellValue('E4', 'SERVIÇOS DE PROGRAMAÇÃO');
        $sheet->setCellValue('F4', '25852');
        $sheet->setCellValue('G4', 'Desenvolvimento Java Web Core');
        $sheet->setCellValue('H4', 'Ativo');

        $sheet->setCellValue('A5', 'Serviço');
        $sheet->setCellValue('B5', '222');
        $sheet->setCellValue('C5', 'MANUTENÇÃO DE PRÉDIO');
        $sheet->setCellValue('D5', '2221');
        $sheet->setCellValue('E5', 'REPARO ELÉTRICO');
        $sheet->setCellValue('F5', '99124');
        $sheet->setCellValue('G5', 'Instalação de Tomadas e Disjuntores');
        $sheet->setCellValue('H5', 'Inativo');

        $tempPath = tempnam(sys_get_temp_dir(), 'catser_test_import_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        // 2. Call the artisan command
        $exitCode = Artisan::call('catalog:import-xlsx', [
            'filePath' => $tempPath,
            'type' => 'service'
        ]);

        $this->assertEquals(0, $exitCode);

        // 3. Assert Services are imported
        $srv1 = CatalogService::where('service_code', '25852')->first();
        $this->assertNotNull($srv1);
        $this->assertEquals('Desenvolvimento Java Web Core', $srv1->description);
        $this->assertEquals(111, $srv1->group_code);
        $this->assertEquals('DESENVOLVIMENTO DE SOFTWARE', $srv1->group_name);
        $this->assertTrue($srv1->is_active);

        $srv2 = CatalogService::where('service_code', '99124')->first();
        $this->assertNotNull($srv2);
        $this->assertEquals('Instalação de Tomadas e Disjuntores', $srv2->description);
        $this->assertEquals(222, $srv2->group_code);
        $this->assertEquals('MANUTENÇÃO DE PRÉDIO', $srv2->group_name);
        $this->assertFalse($srv2->is_active); // Must detect "Inativo" status string

        // 4. Assert Taxonomy Nodes are created
        $groupTax = GovCatalogTaxonomy::where('catalog_type', 'service')->where('level_name', 'group')->where('code', '111')->first();
        $this->assertNotNull($groupTax);
        $this->assertEquals('DESENVOLVIMENTO DE SOFTWARE', $groupTax->description);

        $classTax = GovCatalogTaxonomy::where('catalog_type', 'service')->where('level_name', 'class')->where('code', '1111')->first();
        $this->assertNotNull($classTax);
        $this->assertEquals('SERVIÇOS DE PROGRAMAÇÃO', $classTax->description);
        $this->assertEquals('111', $classTax->parent_code);

        // 5. Assert Progress State is updated to completed
        $progress = Cache::get('etl_progress');
        $this->assertEquals('completed', $progress['status']);
        $this->assertEquals(100, $progress['progress']);
        $this->assertStringContainsString('Importação de serviços finalizada', $progress['message']);

        @unlink($tempPath);
    }
}
