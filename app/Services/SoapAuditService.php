<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SoapAuditService
{
    protected string $url;

    public function __construct()
    {
        $this->url = (string) config('services.soap.audit_url');
    }

    /**
     * Send the required UserLogin audit after a successful SSO login.
     */
    public function auditLogin(string $token, string $email): array
    {
        $xmlPayload = $this->buildLoginAuditEnvelope($email);

        try {
            $m2mToken = $this->getMachineToken();

            $response = Http::withToken($m2mToken)
                ->accept('text/xml')
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=UTF-8',
                ])
                ->timeout((int) config('services.sso.timeout', 10))
                ->withBody($xmlPayload, 'text/xml; charset=UTF-8')
                ->post($this->url);

            if ($response->failed()) {
                Log::warning('SOAP login audit request failed.', [
                    'status_code' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'status' => 'Gagal (HTTP '.$response->status().')',
                    'receipt_number' => null,
                    'message' => 'Audit Log gagal dikirim.',
                    'response_xml' => $response->body(),
                ];
            }

            $receiptNumber = $this->parseReceiptNumber($response->body());
            $status = $this->parseXmlElement($response->body(), 'Status') ?? 'Berhasil';

            if ($receiptNumber === null) {
                return [
                    'success' => false,
                    'status' => 'Gagal (receipt tidak ditemukan)',
                    'receipt_number' => null,
                    'message' => 'SOAP merespons, tetapi Receipt Number tidak ditemukan.',
                    'response_xml' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'status' => $status,
                'receipt_number' => $receiptNumber,
                'message' => 'Audit Log berhasil dikirim.',
                'response_xml' => $response->body(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('SOAP login audit connection failed.', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'Gagal',
                'receipt_number' => null,
                'message' => 'Audit Log gagal dikirim.',
                'response_xml' => null,
            ];
        }
    }

    /**
     * Send transaction to Legacy SOAP Audit Service.
     *
     * @param int $winnerId
     * @param string $userEmail
     * @param string $itemName
     * @param float $amount
     * @return string
     */
    public function auditTransaction(
        int $winnerId,
        string $userEmail,
        string $itemName,
        float $amount,
        ?string $token = null,
    ): string
    {
        // 1. Build the SOAP XML request envelope
        $xmlPayload = $this->buildSoapEnvelope($winnerId, $userEmail, $itemName, $amount);

        Log::info("SOAP Audit: Sending request for Winner ID {$winnerId}", [
            'url' => $this->url,
            'payload' => $xmlPayload,
        ]);

        try {
            // 2. Post XML to SOAP Endpoint with timeout
            $m2mToken = $this->getMachineToken();

            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=UTF-8',
            ])
            ->withToken($m2mToken)
            ->timeout(5) // 5 seconds timeout
            ->withBody($xmlPayload, 'text/xml; charset=UTF-8')
            ->post($this->url);

            if ($response->successful()) {
                $responseBody = $response->body();
                Log::info("SOAP Audit: Received response", ['body' => $responseBody]);

                $receiptNumber = $this->parseReceiptNumber($responseBody);
                if ($receiptNumber) {
                    return $receiptNumber;
                }
            } else {
                Log::warning("SOAP Audit: Request failed with status code " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("SOAP Audit: Exception encountered during request", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // 3. Graceful Fallback if service is down/error
        $fallbackReceipt = 'REC-SOAP-FALLBACK-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
        Log::warning("SOAP Audit: Using mock fallback receipt number: {$fallbackReceipt}");
        return $fallbackReceipt;
    }

    /**
     * Build the raw SOAP Envelope.
     */
    protected function buildSoapEnvelope(int $winnerId, string $userEmail, string $itemName, float $amount): string
    {
        $logContent = json_encode([
            'winner_id' => $winnerId,
            'email' => $userEmail,
            'item_name' => $itemName,
            'amount' => $amount,
            'activity' => 'Winner Checkout',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $logContent = str_replace(']]>', ']]]]><![CDATA[>', $logContent);
        $teamId = htmlspecialchars(
            (string) config('services.sso.team_id', 'TEAM-166'),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
    <soap:Body>
        <iae:AuditRequest>
            <iae:TeamID>{$teamId}</iae:TeamID>
            <iae:ActivityName>WinnerCheckout</iae:ActivityName>
            <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
        </iae:AuditRequest>
    </soap:Body>
</soap:Envelope>
XML;
    }

    protected function buildLoginAuditEnvelope(string $email): string
    {
        $logContent = json_encode([
            'email' => $email,
            'activity' => 'Login Success',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $logContent = str_replace(']]>', ']]]]><![CDATA[>', $logContent);
        $teamId = htmlspecialchars(
            (string) config('services.sso.team_id', 'TEAM-166'),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:iae="http://iae.central/audit">
    <soap:Body>
        <iae:AuditRequest>
            <iae:TeamID>{$teamId}</iae:TeamID>
            <iae:ActivityName>UserLogin</iae:ActivityName>
            <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
        </iae:AuditRequest>
    </soap:Body>
</soap:Envelope>
XML;
    }

    /**
     * Parse ReceiptNumber from SOAP response.
     */
    protected function parseReceiptNumber(string $xmlContent): ?string
    {
        return $this->parseXmlElement($xmlContent, 'ReceiptNumber');
    }

    protected function parseXmlElement(string $xmlContent, string $element): ?string
    {
        $quotedElement = preg_quote($element, '/');

        if (preg_match(
            '/<(?:[A-Za-z0-9_-]+:)?'.$quotedElement.'\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_-]+:)?'.$quotedElement.'>/is',
            $xmlContent,
            $matches
        )) {
            return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }

        return null;
    }

    private function getMachineToken(): string
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('services.sso.base_url'), '/'))
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-API-Key' => (string) config('services.sso.api_key'),
                ])
                ->timeout((int) config('services.sso.timeout', 10))
                ->post('/api/v1/auth/token', [
                    'api_key' => config('services.sso.api_key'),
                    'nim' => config('services.sso.nim'),
                ]);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Layanan token M2M IAE tidak dapat dihubungi.', 0, $exception);
        }

        $response->throw();

        $token = $response->json('token')
            ?? $response->json('access_token')
            ?? $response->json('data.token')
            ?? $response->json('data.access_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Response token M2M tidak memuat token.');
        }

        return $token;
    }
}
