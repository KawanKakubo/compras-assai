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
    public function initializeSignature(ProcurementRequest $procurementRequest, \App\Services\Planning\DocumentTemplateService $documentTemplateService)
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
            $type = request()->query('type', 'sd');
            
            if ($procurementRequest->current_step === ProcurementRequest::STEP_ELABORADOR) {
                if ($type === 'etp') {
                    $filePath = $documentTemplateService->generateEtp($procurementRequest);
                } else {
                    $filePath = $documentTemplateService->generateSd($procurementRequest);
                }
                
                $pdfContent = file_get_contents($filePath);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            } else {
                // Load previously signed PDF to accumulate signatures
                $previousStep = null;
                if ($procurementRequest->current_step === ProcurementRequest::STEP_SECRETARIO) {
                    $previousStep = ProcurementRequest::STEP_ELABORADOR;
                } elseif ($procurementRequest->current_step === ProcurementRequest::STEP_GABINETE) {
                    $previousStep = ProcurementRequest::STEP_SECRETARIO;
                }
                
                $previousPath = $previousStep ? "signatures/{$procurementRequest->reference_code}_{$type}_{$previousStep}.pdf" : null;
                
                if ($previousPath && Storage::disk('public')->exists($previousPath)) {
                    $pdfContent = Storage::disk('public')->get($previousPath);
                } else {
                    if ($type === 'etp') {
                        $filePath = $documentTemplateService->generateEtp($procurementRequest);
                    } else {
                        $filePath = $documentTemplateService->generateSd($procurementRequest);
                    }
                    $pdfContent = file_get_contents($filePath);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
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

            // 6. Update local tracking fields with metadata for individual files
            $metadata = $procurementRequest->metadata ?? [];
            $metadata['signatures'][$type] = [
                'uuid' => $result['uuid'],
                'sign_request_uuid' => $result['sign_request_uuid'],
                'status' => 'pendente',
                'sign_url' => $signUrl,
                'signed_at' => null
            ];

            $procurementRequest->update([
                'libresign_uuid' => $result['uuid'],
                'libresign_sign_request_uuid' => $result['sign_request_uuid'],
                'assinatura_status' => 'pendente',
                'pdf_assinado_url' => $signUrl,
                'rejection_reason' => null, // Clear reason when restarting
                'metadata' => $metadata
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
            $message = 'Falha ao integrar com o Assinador. Por favor, tente novamente.';
            
            if (str_contains($e->getMessage(), 'Current user is not logged in')) {
                $message = 'Você não está autenticado no Assinador. Por favor, realize o login no sistema de assinaturas antes de tentar assinar.';
            } elseif (str_contains($e->getMessage(), 'Connection refused')) {
                $message = 'O servidor de assinaturas está fora do ar ou inacessível. Por favor, tente novamente mais tarde.';
            }

            return response()->json([
                'success' => false,
                'message' => $message
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
        $metadata = $procurementRequest->metadata ?? [];
        $signatures = $metadata['signatures'] ?? [];
        
        // Fallback for old records without metadata
        if (empty($signatures) && $procurementRequest->libresign_uuid) {
            $signatures['sd'] = [
                'uuid' => $procurementRequest->libresign_uuid,
                'status' => 'pendente'
            ];
        }

        $updated = false;
        $anyPending = false;
        $pendingSignUrl = null;

        try {
            foreach ($signatures as $type => $sig) {
                if (($sig['status'] ?? '') === 'pendente' && isset($sig['uuid'])) {
                    $anyPending = true;
                    $uuid = $sig['uuid'];
                    
                    if (!$pendingSignUrl) {
                        $pendingSignUrl = $sig['sign_url'] ?? null;
                    }
                    
                    $check = $this->libresign->checkSignatureStatus($uuid);
                    
                    if ($check['status'] === 3) {
                        $metadata['signatures'][$type]['status'] = 'concluido';
                        $metadata['signatures'][$type]['signed_at'] = now()->toDateTimeString();
                        $updated = true;

                        // Download and save the file
                        $stagePath = "signatures/{$procurementRequest->reference_code}_{$type}_{$procurementRequest->current_step}.pdf";
                        
                        if (config('services.libresign.bypass', true)) {
                            $documentTemplateService = app(\App\Services\Planning\DocumentTemplateService::class);
                            if ($type === 'etp') {
                                $filePath = $documentTemplateService->generateEtp($procurementRequest);
                            } else {
                                $filePath = $documentTemplateService->generateSd($procurementRequest);
                            }
                            $pdfContent = file_get_contents($filePath);
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        } else {
                            $pdfContent = $this->libresign->downloadSignedPdf($uuid);
                        }

                        \Illuminate\Support\Facades\Storage::disk('public')->put($stagePath, $pdfContent);
                        
                        // Update the main model fields for the LAST signed file
                        $procurementRequest->signed_file_path = $stagePath;
                        $procurementRequest->signature_hash = hash('sha256', $pdfContent);
                    }
                }
            }

            if ($updated) {
                $sdStatus = $metadata['signatures']['sd']['status'] ?? 'pendente';
                $etpStatus = $metadata['signatures']['etp']['status'] ?? 'pendente';

                // We NO LONGER auto-forward to Gabinete!
                // The Secretary must manually forward after signing!
                
                $procurementRequest->update([
                    'metadata' => $metadata,
                    'signed_at' => now(),
                    'assinatura_status' => ($sdStatus === 'concluido' && $etpStatus === 'concluido') ? 'assinado' : 'parcialmente_assinado'
                ]);

                session()->flash('success', 'Assinaturas verificadas e atualizadas com sucesso!');

                return response()->json([
                    'success' => true,
                    'message' => 'Assinatura digital autenticada e registrada com sucesso!',
                    'redirect' => url('/secretaria/dashboard')
                ]);
            }

            if (!$anyPending) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma assinatura pendente de verificação para este documento.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Os documentos ainda estão pendentes de assinatura no Nextcloud LibreSign.',
                'sign_url' => $pendingSignUrl
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao verificar status da assinatura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Falha ao verificar status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Encaminha a demanda para o Gabinete.
     */
    public function forwardToGabinete(ProcurementRequest $procurementRequest)
    {
        $metadata = $procurementRequest->metadata ?? [];
        $sdStatus = $metadata['signatures']['sd']['status'] ?? 'pendente';
        $etpStatus = $metadata['signatures']['etp']['status'] ?? 'pendente';

        if ($sdStatus !== 'concluido' || $etpStatus !== 'concluido') {
            return response()->json([
                'success' => false,
                'message' => 'Os documentos precisam estar assinados pelo Secretário antes de encaminhar para o Gabinete.'
            ], 400);
        }

        $procurementRequest->update([
            'status' => ProcurementRequest::STATUS_EM_ANALISE,
            'current_step' => ProcurementRequest::STEP_GABINETE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demanda encaminhada para o Gabinete com sucesso!',
            'redirect' => url('/secretaria/dashboard')
        ]);
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
