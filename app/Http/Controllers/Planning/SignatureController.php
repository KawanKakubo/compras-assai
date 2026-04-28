<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Planning\ProcurementRequest;
use App\Services\AssinadorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    protected AssinadorService $assinador;

    public function __construct(AssinadorService $assinador)
    {
        $this->assinador = $assinador;
    }

    /**
     * Passo 1 e 2: Obtém JWT do Gov e solicita desafio MFA no Assinador.
     */
    public function requestMfa(ProcurementRequest $procurementRequest)
    {
        // BYPASS para desenvolvimento
        if (config('assinador.bypass') && app()->environment('local')) {
            return response()->json([
                'success' => true,
                'challenge_id' => 'mock_challenge_' . $procurementRequest->id,
                'message' => '[MOCK] Modo de teste ativado. Digite qualquer código para prosseguir.'
            ]);
        }

        try {
            $user = auth()->user();
            
            // 1. Gera o conteúdo do PDF para obter o Hash (Integridade)
            $pdfContent = $this->generatePdfContent($procurementRequest);
            $docHash = hash('sha256', $pdfContent);
            $docUuid = (string) $procurementRequest->id;

            // 2. Solicita Token JWT ao Gov.Assaí (Passo 1 do Guia)
            // Aqui enviamos os dados do signatário e o hash do documento
            $jwt = $this->assinador->getGovSigningToken([
                'cpf' => preg_replace('/\D/', '', $procurementRequest->requester_cpf),
                'name' => $procurementRequest->requester_name ?? $user->name,
                'email' => $user->email,
                'doc_uuid' => $docUuid,
                'doc_hash' => $docHash,
            ]);

            // 3. Inicia o desafio MFA no Assinador (Passo 2 do Guia)
            $challenge = $this->assinador->requestMfa($jwt, $docUuid, $docHash, 'whatsapp');

            // Armazena o JWT e o Hash na sessão para o próximo passo (segurança)
            session([
                "signature_jwt_{$docUuid}" => $jwt,
                "signature_hash_{$docUuid}" => $docHash
            ]);

            return response()->json([
                'success' => true,
                'challenge_id' => $challenge['id'] ?? $challenge['challenge_id'],
                'message' => 'Código enviado via WhatsApp.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao solicitar MFA: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar fluxo de assinatura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Passo 3 e 4: Verifica código MFA e realiza a assinatura definitiva.
     */
    public function sign(Request $request, ProcurementRequest $procurementRequest)
    {
        // BYPASS para desenvolvimento
        if (config('assinador.bypass') && app()->environment('local')) {
            $pdfContent = $this->generatePdfContent($procurementRequest);
            $path = 'signatures/' . $procurementRequest->reference_code . '_mocked.pdf';
            Storage::put('public/' . $path, $pdfContent);

            $procurementRequest->update([
                'status' => ProcurementRequest::STATUS_EM_ANALISE,
                'signed_at' => now(),
                'signature_hash' => 'MOCKED_SIGNATURE_' . uniqid(),
                'signed_file_path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => '[MOCK] Documento autorizado com sucesso (Sem Assinatura Digital)!',
                'redirect' => route('planning.module-one.show', $procurementRequest)
            ]);
        }

        $request->validate([
            'mfa_code' => 'required|string|size:6',
        ]);

        $docUuid = (string) $procurementRequest->id;
        $jwt = session("signature_jwt_{$docUuid}");
        $challengeId = $request->challenge_id; // Frontend deve passar isso

        if (!$jwt) {
            return response()->json([
                'success' => false,
                'message' => 'Sessão de assinatura expirada. Inicie o processo novamente.'
            ], 403);
        }

        try {
            // 1. Verifica Código e obtém Autorização de Assinatura (Passo 3 do Guia)
            $signingToken = $this->assinador->verifyMfa($jwt, $challengeId, $request->mfa_code);

            // 2. Gera o PDF novamente para garantir que nada mudou
            $pdfContent = $this->generatePdfContent($procurementRequest);

            // 3. Realiza a assinatura avançada (Passo 4 do Guia)
            $result = $this->assinador->signAdvanced($signingToken, $pdfContent, $docUuid);

            // 4. Salva o PDF assinado
            $path = 'signatures/' . $procurementRequest->reference_code . '_signed.pdf';
            Storage::put('public/' . $path, $result['pdf_content']);

            // 5. Atualiza o banco de dados - Já envia para o Gabinete
            $procurementRequest->update([
                'status' => ProcurementRequest::STATUS_EM_ANALISE,
                'signed_at' => now(),
                'signature_hash' => $result['hash'],
                'signed_file_path' => $path,
            ]);

            // Limpa a sessão
            session()->forget(["signature_jwt_{$docUuid}", "signature_hash_{$docUuid}"]);

            return response()->json([
                'success' => true,
                'message' => 'Documento assinado com sucesso!',
                'redirect' => route('planning.module-one.show', $procurementRequest)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na assinatura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao assinar: ' . $e->getMessage()
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
            'prioridades' => config('compras.prioridades'),
            'secretarias' => config('compras.secretarias'),
            'legalFraming' => $this->calculateLegalFraming($procurementRequest)
        ])->render();

        return Pdf::loadHTML($html)->output();
    }

    protected function calculateLegalFraming($request)
    {
        $total = $request->items->sum('total_value');
        $thresholds = config('compras.lei_14133.dispensa.art75');
        
        $hasServices = $request->items->where('item_type', 'service')->count() > 0;
        
        if ($hasServices && $total <= $thresholds['inciso_ii']) return 'dispensa_servico';
        if (!$hasServices && $total <= $thresholds['inciso_i']) return 'dispensa_material';
        
        return 'licitacao';
    }
}
