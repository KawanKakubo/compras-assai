<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class LibreSignService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $signerAccount;
    protected bool $bypass;

    public function __construct()
    {
        $this->baseUrl = config('services.libresign.base_url', 'https://assinador.assai.pr.gov.br/ocs/v2.php/apps/libresign/api/v1');
        $this->bypass = (bool) config('services.libresign.bypass', true);
        $this->resolveCredentials();
    }

    /**
     * Resolves credentials based on authenticated user or fallback config.
     */
    protected function resolveCredentials(): void
    {
        $user = auth()->user();

        if ($user && !empty($user->libresign_username)) {
            $this->username = $user->libresign_username;
            $this->signerAccount = $user->libresign_signer_account ?? $user->email;
            
            try {
                $this->password = Crypt::decryptString($user->libresign_password);
            } catch (\Exception $e) {
                $this->password = $user->libresign_password ?? '';
            }
        } else {
            $this->username = config('services.libresign.username') ?? '';
            $this->password = config('services.libresign.password') ?? '';
            $this->signerAccount = config('services.libresign.signer') ?? '';
        }
    }

    /**
     * Helper to get standard API headers.
     */
    protected function getHeaders(): array
    {
        return [
            'OCS-APIRequest' => 'true',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Requests a signature for a PDF file.
     * 
     * @param string $filename Name of the file in LibreSign
     * @param string $pdfContent Raw binary content of the PDF
     * @param string|null $signerAccount Optional specific signer account
     * @return array [uuid, sign_request_uuid, sign_url]
     */
    public function requestSignature(string $filename, string $pdfContent, ?string $signerAccount = null): array
    {
        $targetSigner = $signerAccount ?? $this->signerAccount;

        if ($this->bypass) {
            Log::info('LibreSign Service: Requesting Signature (BYPASS MOCK)', [
                'filename' => $filename,
                'signer' => $targetSigner
            ]);

            $mockUuid = 'mock-uuid-' . bin2hex(random_bytes(8));
            $mockSignRequestUuid = 'mock-sign-req-' . bin2hex(random_bytes(8));
            
            return [
                'uuid' => $mockUuid,
                'sign_request_uuid' => $mockSignRequestUuid,
                'sign_url' => "https://assinador.assai.pr.gov.br/apps/libresign/f/sign/{$mockUuid}/pdf"
            ];
        }

        try {
            $base64Pdf = base64_encode($pdfContent);
            $payload = [
                'name' => $filename,
                'file' => [
                    'url' => 'data:application/pdf;base64,' . $base64Pdf
                ],
                'signers' => [
                    [
                        'identifyMethods' => [
                            [
                                'method' => 'account',
                                'value' => $targetSigner,
                                'mandatory' => 1
                            ]
                        ]
                    ]
                ]
            ];

            Log::info('Sending request to LibreSign', [
                'url' => $this->baseUrl . '/request-signature',
                'username' => $this->username,
                'signer' => $targetSigner
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->withBasicAuth($this->username, $this->password)
                ->post($this->baseUrl . '/request-signature', $payload);

            if ($response->failed()) {
                Log::error('LibreSign request-signature failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \RuntimeException('Falha ao registrar documento no LibreSign: ' . $response->body());
            }

            $data = $response->json();
            
            // OCS API standard wraps responses in ocs -> data
            $ocsData = $data['ocs']['data'] ?? null;
            if (!$ocsData) {
                Log::error('LibreSign signature request returned invalid JSON format', ['data' => $data]);
                throw new \RuntimeException('Estrutura de dados inválida retornada pelo LibreSign.');
            }

            // OCS data can wrap properties differently sometimes depending on Nextcloud version
            $uuid = $ocsData['uuid'] ?? $ocsData['data']['uuid'] ?? null;
            $signRequestUuid = $ocsData['sign_request_uuid'] ?? $ocsData['data']['sign_request_uuid'] ?? null;

            if (!$uuid) {
                Log::error('UUID not found in LibreSign response', ['data' => $data]);
                throw new \RuntimeException('Não foi possível recuperar o UUID do documento no LibreSign.');
            }

            return [
                'uuid' => $uuid,
                'sign_request_uuid' => $signRequestUuid,
                'sign_url' => "https://assinador.assai.pr.gov.br/apps/libresign/f/sign/{$uuid}/pdf"
            ];

        } catch (\Exception $e) {
            Log::error('Error calling LibreSign requestSignature', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Checks the status of a document in LibreSign.
     * 
     * @param string $uuid Document UUID
     * @return array [status, signers]
     */
    public function checkSignatureStatus(string $uuid): array
    {
        if ($this->bypass) {
            Log::info('LibreSign Service: Check Signature Status (BYPASS MOCK)', ['uuid' => $uuid]);
            return [
                'status' => 3, // 3 = Fully Signed (Concluído)
                'status_label' => 'Concluído',
                'signers' => []
            ];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withBasicAuth($this->username, $this->password)
                ->get($this->baseUrl . '/file/list', [
                    'uuid' => $uuid,
                    'details' => 1
                ]);

            if ($response->failed()) {
                Log::error('LibreSign checkStatus failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \RuntimeException('Falha ao checar status no LibreSign: ' . $response->body());
            }

            $data = $response->json();
            $ocsData = $data['ocs']['data'] ?? [];

            // Find specific file details
            $fileDetails = null;
            if (isset($ocsData['list']) && is_array($ocsData['list'])) {
                foreach ($ocsData['list'] as $item) {
                    if (($item['uuid'] ?? '') === $uuid) {
                        $fileDetails = $item;
                        break;
                    }
                }
            } else {
                $fileDetails = $ocsData;
            }

            if (!$fileDetails) {
                Log::warning('Document UUID not found in list response', ['uuid' => $uuid, 'data' => $data]);
                return [
                    'status' => 0,
                    'status_label' => 'Não encontrado',
                    'signers' => []
                ];
            }

            $statusCode = (int) ($fileDetails['status'] ?? 0);
            $statusLabels = [
                0 => 'Não definido',
                1 => 'Pronto para assinar',
                2 => 'Parcialmente assinado',
                3 => 'Concluído',
                4 => 'Excluído'
            ];

            return [
                'status' => $statusCode,
                'status_label' => $statusLabels[$statusCode] ?? 'Desconhecido',
                'signers' => $fileDetails['signers'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Error checking LibreSign status', [
                'uuid' => $uuid,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Downloads the final signed PDF binary.
     * 
     * @param string $fileUuid Document UUID (or file_uuid)
     * @return string Raw PDF content
     */
    public function downloadSignedPdf(string $fileUuid): string
    {
        if ($this->bypass) {
            Log::info('LibreSign Service: Downloading Signed PDF (BYPASS MOCK)', ['file_uuid' => $fileUuid]);
            // Returns an empty or placeholder PDF or whatever we can write, we will handle this in controllers.
            return '';
        }

        try {
            // Endpoints of the format: /apps/libresign/p/pdf/{file_uuid}
            // Note that this uses the same domain as the baseUrl, but is outside of /ocs/v2.php/apps/libresign/api/v1
            $parsedUrl = parse_url($this->baseUrl);
            $hostUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'assinador.assai.pr.gov.br');
            
            $downloadUrl = "{$hostUrl}/apps/libresign/p/pdf/{$fileUuid}";

            Log::info('Downloading signed PDF from LibreSign', ['url' => $downloadUrl]);

            $response = Http::get($downloadUrl);

            if ($response->failed()) {
                Log::error('Failed to download signed PDF from LibreSign', [
                    'status' => $response->status(),
                    'url' => $downloadUrl
                ]);
                throw new \RuntimeException('Falha ao baixar PDF assinado do LibreSign.');
            }

            return $response->body();

        } catch (\Exception $e) {
            Log::error('Error downloading PDF from LibreSign', [
                'fileUuid' => $fileUuid,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
