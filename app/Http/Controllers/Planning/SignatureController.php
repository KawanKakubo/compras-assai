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
     * Passo 1: Solicita desafio MFA via WhatsApp
     */
    public function requestMfa(ProcurementRequest $procurementRequest)
    {
        $user = auth()->user();
        
        // Tenta pegar o celular do usuário (se existir campo no banco, senão mockamos para teste)
        // O guia diz que o Assinador gera o código e dispara webhook para o Gov
        try {
            $cpf = preg_replace('/\D/', '', $procurementRequest->requester_cpf);
            
            // Inicia o desafio no Assinador
            $challenge = $this->assinador->requestMfa($cpf, 'whatsapp');

            return response()->json([
                'success' => true,
                'challenge_id' => $challenge['challenge_id'] ?? 'dummy_id',
                'message' => 'Código enviado via WhatsApp.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao solicitar MFA: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Passo 2: Verifica código e assina
     */
    public function sign(Request $request, ProcurementRequest $procurementRequest)
    {
        $request->validate([
            'mfa_code' => 'required|string|size:6',
        ]);

        try {
            $user = auth()->user();
            
            // 1. Gera o PDF do documento (SD) para assinatura
            // Reutilizamos a lógica da view para gerar o HTML e converter em PDF
            $html = view('planning.module-one.pdf_template', [
                'procurementRequest' => $procurementRequest,
                'items' => $procurementRequest->items,
                'study' => $procurementRequest->studies->first(),
                'totalEstimated' => $procurementRequest->items->sum('total_value'),
                'prioridades' => config('compras.prioridades'),
                'secretarias' => config('compras.secretarias'),
                'legalFraming' => $this->calculateLegalFraming($procurementRequest)
            ])->render();

            $pdf = Pdf::loadHTML($html);
            $pdfContent = $pdf->output();

            // 2. Solicita Token JWT (RS256) ao Gov IdP
            $tokenResponse = Http::post(config('assinador.gov_idp_url') . '/api/internal/issue-signing-token', [
                'sub' => (string) $user->id,
                'cpf' => preg_replace('/\D/', '', $procurementRequest->requester_cpf),
                'name' => $user->name,
                'doc_uuid' => (string) $procurementRequest->id,
                'doc_hash' => hash('sha256', $pdfContent),
                'scope' => 'sign:advanced',
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Falha ao obter token de assinatura do IdP.');
            }

            $jwt = $tokenResponse->json('token');

            // 3. Chama o Assinador para aplicar a assinatura PAdES
            $result = $this->assinador->signAdvanced($jwt, $pdfContent, $request->mfa_code, (string) $procurementRequest->id);

            // 4. Salva o PDF assinado e atualiza status
            $path = 'signatures/' . $procurementRequest->reference_code . '_signed.pdf';
            Storage::put('public/' . $path, $result['pdf_content']);

            $procurementRequest->status = ProcurementRequest::STATUS_ASSINADO;
            $procurementRequest->signed_at = now();
            $procurementRequest->signed_file_path = $path;
            $procurementRequest->save();

            $procurementRequest->update([
                'signed_at' => now(),
                'signature_hash' => $result['hash'],
                'signed_file_path' => $path,
            ]);

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
