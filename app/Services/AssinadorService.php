<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AssinadorService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl = config('assinador.base_url');
        $this->timeout = config('assinador.timeout', 60);
        $this->connectTimeout = config('assinador.connect_timeout', 20);
    }

    /**
     * Obtém um token de assinatura interno do Gov.Assaí.
     * Segue o Passo 1 do Guia de Integração.
     */
    public function getGovSigningToken(array $userData): string
    {
        Log::info('Solicitando token de assinatura ao Gov.Assaí', ['cpf' => $userData['cpf'] ?? 'N/A']);

        $response = Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->post(config('assinador.gov_idp_url') . '/api/internal/issue-signing-token', $userData);

        if (!$response->successful()) {
            Log::error('Falha ao obter token no Gov.Assaí', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new RuntimeException('Falha ao obter token de assinatura no Gov.Assaí. Verifique a conexão com o IdP.');
        }

        return $response->json('token');
    }

    /**
     * Inicia o desafio MFA para o cidadão.
     * Segue o Passo 2 do Guia de Integração.
     */
    public function requestMfa(string $jwt, string $docUuid, string $docHash, string $channel = 'whatsapp'): array
    {
        Log::info('Iniciando desafio MFA no Assinador', [
            'doc_uuid' => $docUuid,
            'url' => $this->baseUrl . '/v1/auth/mfa/challenges'
        ]);

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($jwt)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->post('/v1/auth/mfa/challenges', [
                'doc_uuid' => $docUuid,
                'doc_hash' => $docHash,
                'channel' => $channel,
            ]);

        if (!$response->successful()) {
            $error = $this->extractError($response);
            Log::error('Erro ao solicitar MFA no Assinador', [
                'error' => $error,
                'status' => $response->status()
            ]);
            throw new RuntimeException("Erro ao solicitar MFA: " . $error);
        }

        return $response->json();
    }

    /**
     * Verifica o código MFA e retorna o token de autorização de assinatura.
     * Segue o Passo 3 do Guia de Integração.
     */
    public function verifyMfa(string $jwt, string $challengeId, string $code): string
    {
        Log::info('Verificando código MFA', ['challenge_id' => $challengeId]);

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($jwt)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->post("/v1/auth/mfa/challenges/{$challengeId}/verify", [
                'code' => $code,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException("Código inválido ou expirado: " . $this->extractError($response));
        }

        return $response->json('signing_token');
    }

    /**
     * Realiza a assinatura avançada do PDF usando o token de autorização.
     * Segue o Passo 4 do Guia de Integração.
     */
    public function signAdvanced(string $signingToken, string $pdfContent, string $docUuid): array
    {
        Log::info('Realizando assinatura avançada', ['doc_uuid' => $docUuid]);

        $response = Http::baseUrl($this->baseUrl)
            ->withToken($signingToken)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->attach('pdf_file', $pdfContent, "documento_{$docUuid}.pdf")
            ->post('/v1/signatures/advanced', [
                'doc_uuid' => $docUuid,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException("Erro na assinatura avançada: " . $this->extractError($response));
        }

        return [
            'pdf_content' => $response->body(),
            'hash' => $response->header('X-Document-Hash'),
            'authenticity_code' => $response->header('X-Authenticity-Code'),
            'signed_at' => now()->toDateTimeString(),
        ];
    }

    protected function extractError($response): string
    {
        $data = $response->json();
        return $data['detail'] ?? $data['message'] ?? $data['error'] ?? 'Erro na comunicação com o assinador.';
    }
}
