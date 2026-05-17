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
    }

    /**
     * Resolves credentials based on authenticated user or fallback config.
     * Must be called inside methods to ensure auth()->user() is available.
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
     * Sends a document to LibreSign for digital signature.
     * Supports multiple signers and visual placement.
     */
    public function requestSignature($filename, $pdfContent, $signers = [], $visualPosition = null)
    {
        $this->resolveCredentials();

        // If $signers is a string (legacy support), convert to array
        if (is_string($signers)) {
            $signers = [
                [
                    'identifyMethods' => [
                        [
                            'method' => 'account',
                            'value' => $signers,
                            'mandatory' => 1
                        ]
                    ]
                ]
            ];
        }

        // Default visual position if not provided
        $visualPosition = $visualPosition ?? [
            'page' => 1,
            'x' => 350,
            'y' => 700,
            'width' => 200,
            'height' => 100
        ];

        if (config('services.libresign.bypass', true)) {
            $mockUuid = 'mock-doc-' . bin2hex(random_bytes(8));
            $mockSignRequestUuid = 'mock-sign-req-' . bin2hex(random_bytes(8));
            
            return [
                'uuid' => $mockUuid,
                'sign_request_uuid' => $mockSignRequestUuid,
                'sign_url' => "https://assinador.assai.pr.gov.br/apps/libresign/f/sign/{$mockUuid}/pdf"
            ];
        }

        try {
            $payload = [
                'name' => $filename,
                'file' => [
                    'base64' => base64_encode($pdfContent)
                ],
                'signers' => $signers,
                'visual_signature' => [
                    'position' => [
                        'page' => $visualPosition['page'] ?? 1,
                        'x' => $visualPosition['x'] ?? 350,
                        'y' => $visualPosition['y'] ?? 700
                    ],
                    'width' => $visualPosition['width'] ?? 200,
                    'height' => $visualPosition['height'] ?? 100
                ]
            ];

            Log::info('Sending request to LibreSign', [
                'url' => $this->baseUrl . '/request-signature',
                'username' => $this->username,
                'signers_count' => count($signers)
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
            
            Log::info('LibreSign raw response (JSON): ' . json_encode($data));
            
            // OCS API standard wraps responses in ocs -> data
            $ocsData = $data['ocs']['data'] ?? null;
            if (!$ocsData) {
                Log::error('LibreSign signature request returned invalid JSON format', ['data' => $data]);
                throw new \RuntimeException('Estrutura de dados inválida retornada pelo LibreSign.');
            }

            // Search deeply for UUIDs using a more robust helper
            $uuid = $this->findKeyInArray($data, 'uuid');
            $signRequestUuid = $this->findKeyInArray($data, 'sign_request_uuid') 
                            ?? $this->findKeyInArray($data, 'signRequestUuid');
            
            // If still not found, check if there is a 'url' field that contains a UUID
            $apiUrl = $this->findKeyInArray($data, 'url') ?? $this->findKeyInArray($data, 'file');
            if (!$signRequestUuid && $apiUrl && is_string($apiUrl)) {
                if (preg_match('/([a-f0-9-]{36})/', $apiUrl, $matches)) {
                    $signRequestUuid = $matches[1];
                    Log::info('Extracted signRequestUuid from URL string', ['uuid' => $signRequestUuid, 'url' => $apiUrl]);
                }
            }

            if (!$uuid) {
                Log::error('UUID not found in LibreSign response', ['data' => $data]);
                throw new \RuntimeException('Não foi possível recuperar o UUID do documento no LibreSign.');
            }

            // Auto-healing: if sign_request_uuid is missing from creation, poll the status
            if (!$signRequestUuid) {
                Log::info('sign_request_uuid missing from initial response, attempting auto-healing...', ['uuid' => $uuid]);
                $statusRes = $this->checkSignatureStatus($uuid);
                if (isset($statusRes['raw_data'])) {
                    $signRequestUuid = $this->findKeyInArray($statusRes['raw_data'], 'sign_request_uuid');
                }
            }

            // Fallback: if we STILL don't have it, we use the uuid but log it
            // Note: LibreSign f/sign/ usually REQUIRES sign_request_uuid
            $sessionUuid = $signRequestUuid ?? $uuid;
            
            if (!$signRequestUuid) {
                Log::warning('LibreSign: Using document uuid as session uuid fallback. Redirection might fail.', ['uuid' => $uuid]);
            }

            $parsedUrl = parse_url($this->baseUrl);
            $hostUrl = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'assinador.assai.pr.gov.br');

            return [
                'uuid' => $uuid,
                'sign_request_uuid' => $signRequestUuid,
                'sign_url' => "{$hostUrl}/apps/libresign/f/sign/{$sessionUuid}/pdf"
            ];

        } catch (\Exception $e) {
            Log::error('LibreSign requestSignature exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recursively find a key in a nested array
     */
    private function findKeyInArray(array $array, string $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        foreach ($array as $item) {
            if (is_array($item)) {
                $result = $this->findKeyInArray($item, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Checks the status of a document in LibreSign.
     * 
     * @param string $uuid Document UUID
     * @return array [status, signers]
     */
    public function checkSignatureStatus(string $uuid): array
    {
        $this->resolveCredentials();
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
        $this->resolveCredentials();
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
