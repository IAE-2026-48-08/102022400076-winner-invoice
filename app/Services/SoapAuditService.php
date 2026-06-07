<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SoapAuditService
{
    protected string $url;

    public function __construct()
    {
        $this->url = config('services.soap.audit_url', env('SOAP_AUDIT_URL', 'http://cloud-dosen.test/soap/audit'));
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
    public function auditTransaction(int $winnerId, string $userEmail, string $itemName, float $amount): string
    {
        // 1. Build the SOAP XML request envelope
        $xmlPayload = $this->buildSoapEnvelope($winnerId, $userEmail, $itemName, $amount);

        Log::info("SOAP Audit: Sending request for Winner ID {$winnerId}", [
            'url' => $this->url,
            'payload' => $xmlPayload,
        ]);

        try {
            // 2. Post XML to SOAP Endpoint with timeout
            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://audit.enterprise.digital.city/AuditTransaction',
            ])
            ->timeout(5) // 5 seconds timeout
            ->withBody($xmlPayload, 'text/xml')
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
        $itemNameClean = htmlspecialchars($itemName, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $userEmailClean = htmlspecialchars($userEmail, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:aud="http://audit.enterprise.digital.city">
   <soapenv:Header/>
   <soapenv:Body>
      <aud:AuditRequest>
         <aud:WinnerId>{$winnerId}</aud:WinnerId>
         <aud:UserEmail>{$userEmailClean}</aud:UserEmail>
         <aud:ItemName>{$itemNameClean}</aud:ItemName>
         <aud:Amount>{$amount}</aud:Amount>
      </aud:AuditRequest>
   </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    /**
     * Parse ReceiptNumber from SOAP response.
     */
    protected function parseReceiptNumber(string $xmlContent): ?string
    {
        try {
            // Disable external entities loading for security
            $previousEntityState = libxml_disable_entity_loader(true);
            
            // Clean up namespaces to make parsing easier
            $xmlClean = preg_replace('/(<\/?[a-zA-Z0-9_-]+):([^>]+>)/', '$1$2', $xmlContent);
            $xml = simplexml_load_string($xmlClean);
            
            libxml_disable_entity_loader($previousEntityState);

            if ($xml === false) {
                // Try simple string extraction if XML parsing fails due to bad formatting
                if (preg_replace('/.*<ReceiptNumber>(.*?)<\/ReceiptNumber>.*/is', '$1', $xmlContent, 1, $count) && $count > 0) {
                    $extracted = preg_replace('/.*<ReceiptNumber>(.*?)<\/ReceiptNumber>.*/is', '$1', $xmlContent);
                    return trim($extracted);
                }
                return null;
            }

            // Search for ReceiptNumber anywhere in the XML
            $results = $xml->xpath('//ReceiptNumber');
            if (!empty($results)) {
                return (string) $results[0];
            }
        } catch (\Exception $e) {
            Log::error("SOAP Audit: XML Parsing Exception", ['message' => $e->getMessage()]);
        }

        // Regex fallback
        if (preg_match('/<ReceiptNumber>(.*?)<\/ReceiptNumber>/i', $xmlContent, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
