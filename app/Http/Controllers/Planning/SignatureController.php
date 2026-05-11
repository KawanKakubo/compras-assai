<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Planning\ProcurementRequest;
use App\Services\LibreSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    protected LibreSignService $libresign;

    public function __construct(LibreSignService $libresign)
    {
        $this->libresign = $libresign;
    }

    /**
     * Initializes a signature request with LibreSign.
     */
    public function initializeSignature(ProcurementRequest $procurementRequest)
    {
        $user = auth()->user();

        // 1. Authorization checks based on status and user role
        if ($procurementRequest->status === ProcurementRequest::STATUS_RASCUNHO && !$user->isElaborador()) {
            return response()->json([
                'success' => false,
                'message' => 'Somente o elaborador da demanda pode assinar nesta etapa.'
            ], 403);
        }

        if ($procurementRequest->status === ProcurementRequest::STATUS_ASSINADO && !$user->isSecretario()) {
            return response()->json([
                'success' => false,
                'message' => 'Somente o Secretário requisitante pode assinar nesta etapa.'
            ], 403);
        }

        if ($procurementRequest->status === ProcurementRequest::STATUS_EM_ANALISE && !$user->isGabinete()) {
            return response()->json([
                'success' => false,
                'message' => 'Somente um membro do Gabinete pode autorizar e assinar nesta etapa.'
            ], 403);
        }

        try {
            // 2. Resolve PDF content (First stage generates fresh PDF, later stages load previous PDF)
            if ($procurementRequest->status === ProcurementRequest::STATUS_RASCUNHO) {
                $pdfContent = $this->generatePdfContent($procurementRequest);
                $stageName = 'elaborador';
            } else {
                // Load previously signed PDF to accumulate signatures
                if ($procurementRequest->signed_file_path && Storage::disk('public')->exists($procurementRequest->signed_file_path)) {
                    $pdfContent = Storage::disk('public')->get($procurementRequest->signed_file_path);
                } else {
                    $pdfContent = $this->generatePdfContent($procurementRequest);
                }
                $stageName = $procurementRequest->status === ProcurementRequest::STATUS_ASSINADO ? 'secretario' : 'gabinete';
            }

            $filename = "{$procurementRequest->reference_code}_assinatura_{$stageName}.pdf";

            // 3. Request signature on Nextcloud LibreSign
            // Pass the user's LibreSign account if configured, otherwise falls back to default service credentials
            $signerAccount = $user->libresign_signer_account ?? $user->email;
            
            $result = $this->libresign->requestSignature($filename, $pdfContent, $signerAccount);

            // 4. If bypass is enabled, customize the sign URL to point to our callback
            $signUrl = $result['sign_url'];
            if (config('services.libresign.bypass', true)) {
                $signUrl = route('planning.signature.callback', [
                    'procurementRequest' => $procurementRequest,
                    'uuid' => $result['uuid']
                ]);
            }

            // 5. Update local tracking fields
            $procurementRequest->update([
                'libresign_uuid' => $result['uuid'],
                'libresign_sign_request_uuid' => $result['sign_request_uuid'],
                'assinatura_status' => 'pendente',
                'pdf_assinado_url' => $signUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de assinatura iniciada com sucesso.',
                'sign_url' => $signUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao inicializar assinatura LibreSign: ' . $e->getMessage(), [
                'request_id' => $procurementRequest->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Falha ao integrar com o Assinador: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simulation / Callback landing page when returning from signing.
     */
    public function signatureCallback(Request $request, ProcurementRequest $procurementRequest)
    {
        $uuid = $request->query('uuid');

        // Check if bypass mode is on, if not, we handle the callback normally
        $isBypass = (bool) config('services.libresign.bypass', true);

        return view('planning.module-one.signature_callback', [
            'procurementRequest' => $procurementRequest,
            'uuid' => $uuid,
            'isBypass' => $isBypass
        ]);
    }

    /**
     * Checks and finalized signature status verification.
     */
    public function verifySignature(ProcurementRequest $procurementRequest)
    {
        $uuid = $procurementRequest->libresign_uuid;

        if (!$uuid || $procurementRequest->assinatura_status !== 'pendente') {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma assinatura pendente de verificação para este documento.'
            ]);
        }

        try {
            // Query LibreSign for the status
            $check = $this->libresign->checkSignatureStatus($uuid);

            // Status 3 = Concluído (Signed)
            if ($check['status'] === 3) {
                // Determine new status and target path
                $stagePath = '';
                $nextStatus = '';
                
                if ($procurementRequest->status === ProcurementRequest::STATUS_RASCUNHO) {
                    $stagePath = "signatures/{$procurementRequest->reference_code}_elaborador.pdf";
                    $nextStatus = ProcurementRequest::STATUS_ASSINADO; // Aguardando Secretário
                } elseif ($procurementRequest->status === ProcurementRequest::STATUS_ASSINADO) {
                    $stagePath = "signatures/{$procurementRequest->reference_code}_secretario.pdf";
                    $nextStatus = ProcurementRequest::STATUS_EM_ANALISE; // Aguardando Gabinete
                } elseif ($procurementRequest->status === ProcurementRequest::STATUS_EM_ANALISE) {
                    $stagePath = "signatures/{$procurementRequest->reference_code}_gabinete.pdf";
                    $nextStatus = ProcurementRequest::STATUS_APROVADO_COMPRAS; // Compras
                }

                // Download the signed PDF
                if (config('services.libresign.bypass', true)) {
                    if ($procurementRequest->signed_file_path && Storage::disk('public')->exists($procurementRequest->signed_file_path)) {
                        $pdfContent = Storage::disk('public')->get($procurementRequest->signed_file_path);
                    } else {
                        $pdfContent = $this->generatePdfContent($procurementRequest);
                    }
                } else {
                    $pdfContent = $this->libresign->downloadSignedPdf($uuid);
                }

                // Save signed PDF to public storage
                Storage::disk('public')->put($stagePath, $pdfContent);

                // Update database
                $procurementRequest->update([
                    'status' => $nextStatus,
                    'signed_at' => now(),
                    'signature_hash' => hash('sha256', $pdfContent),
                    'signed_file_path' => $stagePath,
                    'assinatura_status' => 'assinado',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Assinatura digital autenticada e registrada com sucesso!',
                    'redirect' => route('planning.module-one.show', $procurementRequest)
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => $check['status'],
                'message' => 'O documento ainda está pendente de assinatura no Nextcloud LibreSign.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar status da assinatura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Falha ao verificar status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auxiliar para gerar o conteúdo do PDF.
     */
    protected function generatePdfContent(ProcurementRequest $procurementRequest): string
    {
        $html = view('planning.module-one.pdf_template', [
            'procurementRequest' => $procurementRequest,
            'items' => $procurementRequest->items,
            'study' => $procurementRequest->studies->first(),
            'totalEstimated' => $procurementRequest->items->sum('total_value'),
            'prioridades' => config('compras.prioridades', [
                'low' => 'Baixa',
                'medium' => 'Média',
                'high' => 'Alta',
            ]),
            'secretarias' => config('compras.secretarias', []),
            'legalFraming' => $this->calculateLegalFraming($procurementRequest)
        ])->render();

        return Pdf::loadHTML($html)->output();
    }

    protected function calculateLegalFraming($request)
    {
        $total = $request->items->sum('total_value');
        $thresholds = config('compras.lei_14133.dispensa.art75', [
            'inciso_i' => 119812.01,
            'inciso_ii' => 59906.01,
        ]);
        
        $hasServices = $request->items->where('item_type', 'service')->count() > 0;
        
        if ($hasServices && $total <= $thresholds['inciso_ii']) return 'dispensa_servico';
        if (!$hasServices && $total <= $thresholds['inciso_i']) return 'dispensa_material';
        
        return 'licitacao';
    }
}
