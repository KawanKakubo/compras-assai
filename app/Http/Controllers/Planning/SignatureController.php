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

        // 1. Authorization checks based on current workflow step
        if (!$procurementRequest->canBeApprovedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para assinar ou aprovar este documento nesta etapa.'
            ], 403);
        }

        try {
            // 2. Resolve PDF content
            // If it was rejected, we might need to regenerate it if the elaborator made changes
            if ($procurementRequest->current_step === ProcurementRequest::STEP_ELABORADOR) {
                $pdfContent = $this->generatePdfContent($procurementRequest);
            } else {
                // Load previously signed PDF to accumulate signatures
                if ($procurementRequest->signed_file_path && Storage::disk('public')->exists($procurementRequest->signed_file_path)) {
                    $pdfContent = Storage::disk('public')->get($procurementRequest->signed_file_path);
                } else {
                    $pdfContent = $this->generatePdfContent($procurementRequest);
                }
            }

            $stageName = $procurementRequest->current_step;
            $filename = "{$procurementRequest->reference_code}_assinatura_{$stageName}.pdf";

            // 3. Define Visual Position based on Step
            $visualPosition = match ($stageName) {
                ProcurementRequest::STEP_ELABORADOR => ['page' => 1, 'x' => 50, 'y' => 750, 'width' => 180, 'height' => 60],
                ProcurementRequest::STEP_SECRETARIO => ['page' => 1, 'x' => 350, 'y' => 750, 'width' => 180, 'height' => 60],
                ProcurementRequest::STEP_GABINETE   => ['page' => 1, 'x' => 50, 'y' => 680, 'width' => 180, 'height' => 60],
                ProcurementRequest::STEP_COMPRAS    => ['page' => 1, 'x' => 350, 'y' => 680, 'width' => 180, 'height' => 60],
                default => ['page' => 1, 'x' => 50, 'y' => 50, 'width' => 150, 'height' => 50],
            };

            // 4. Request signature on Nextcloud LibreSign
            $signerAccount = $user->libresign_username ?? $user->email;
            
            $result = $this->libresign->requestSignature($filename, $pdfContent, $signerAccount, $visualPosition);

            // 5. If bypass is enabled, customize the sign URL to point to our callback
            $signUrl = $result['sign_url'];
            if (config('services.libresign.bypass', true)) {
                $signUrl = route('planning.signature.callback', [
                    'procurementRequest' => $procurementRequest,
                    'uuid' => $result['uuid']
                ]);
            }

            // 6. Update local tracking fields
            $procurementRequest->update([
                'libresign_uuid' => $result['uuid'],
                'libresign_sign_request_uuid' => $result['sign_request_uuid'],
                'assinatura_status' => 'pendente',
                'pdf_assinado_url' => $signUrl,
                'rejection_reason' => null, // Clear reason when restarting
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
     * Rejects the request and returns it to the previous step (elaborador).
     */
    public function rejectRequest(Request $request, ProcurementRequest $procurementRequest)
    {
        $user = auth()->user();
        
        // Only Secretary or Gabinete can reject
        if (!$user->isSecretario() && !$user->isGabinete()) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para rejeitar este documento.'
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        $procurementRequest->update([
            'status' => ProcurementRequest::STATUS_REJEITADO,
            'current_step' => ProcurementRequest::STEP_ELABORADOR,
            'rejection_reason' => $request->reason,
            'assinatura_status' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demanda devolvida para o elaborador com sucesso.',
            'redirect' => route('planning.module-one.show', $procurementRequest)
        ]);
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
                
                if ($procurementRequest->current_step === ProcurementRequest::STEP_ELABORADOR) {
                    $stagePath = "signatures/{$procurementRequest->reference_code}_elaborador.pdf";
                    $nextStatus = ProcurementRequest::STATUS_ASSINADO; // Aguardando Secretário
                    $nextStep = ProcurementRequest::STEP_SECRETARIO;
                } elseif ($procurementRequest->current_step === ProcurementRequest::STEP_SECRETARIO) {
                    $stagePath = "signatures/{$procurementRequest->reference_code}_secretario.pdf";
                    $nextStatus = ProcurementRequest::STATUS_EM_ANALISE; // Aguardando Gabinete
                    $nextStep = ProcurementRequest::STEP_GABINETE;
                } elseif ($procurementRequest->current_step === ProcurementRequest::STEP_GABINETE) {
                    $stagePath = "signatures/{$procurementRequest->reference_code}_gabinete.pdf";
                    $nextStatus = ProcurementRequest::STATUS_APROVADO_COMPRAS; // Compras
                    $nextStep = ProcurementRequest::STEP_COMPRAS;
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
                    'current_step' => $nextStep,
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
