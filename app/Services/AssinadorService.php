<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AssinadorService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('assinador.base_url');
        $this->timeout = config('assinador.timeout');
    }

    /**
     * Inicia o desafio MFA para o cidadão.
     */
    public function requestMfa(string $cpf, string $channel = 'whatsapp'): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->post('/v1/signatures/mfa/challenge', [
                'signer_cpf' => $cpf,
                'channel' => $channel,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException($this->extractError($response));
        }

        return $response->json();
    }

    /**
     * Realiza a assinatura avançada do PDF.
     */
    public function signAdvanced(string $jwt, string $pdfContent, string $mfaCode, string $docUuid): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($jwt)
            ->timeout($this->timeout)
            ->attach('pdf_file', $pdfContent, "documento_{$docUuid}.pdf")
            ->post('/v1/signatures/advanced', [
                'mfa_code' => $mfaCode,
                'doc_uuid' => $docUuid,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException($this->extractError($response));
        }

        return [
            'pdf_content' => $response->body(),
            'hash' => $response->header('X-Document-Hash'),
            'signed_at' => now()->toDateTimeString(),
        ];
    }

    protected function extractError($response): string
    {
        $data = $response->json();
        return $data['detail'] ?? $data['message'] ?? 'Erro na comunicação com o assinador.';
    }
}
