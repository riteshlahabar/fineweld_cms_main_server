<?php

namespace App\Services\TallyIntegration;

use App\Models\TallyIntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TallyClientService
{
    public function activeSettings(): ?TallyIntegrationSetting
    {
        if (! Schema::hasTable('tally_integration_settings')) {
            return null;
        }

        return TallyIntegrationSetting::query()
            ->where('status', 1)
            ->latest('id')
            ->first();
    }

    public function defaultCompanyName(): ?string
    {
        $companyName = $this->settingString($this->activeSettings(), 'company_name');

        return $companyName !== '' ? $companyName : null;
    }

    public function defaultSalesLedgerName(): string
    {
        $salesLedgerName = $this->settingString($this->activeSettings(), 'sales_ledger_name');

        return $salesLedgerName !== '' ? $salesLedgerName : 'Sales';
    }

    public function resolvedXmlPort(?TallyIntegrationSetting $settings = null): int
    {
        $settings ??= $this->activeSettings();

        foreach (['xml_port', 'port', 'odbc_port'] as $attribute) {
            $port = (int) ($settings?->getAttribute($attribute) ?: 0);
            if ($port > 0) {
                return $port;
            }
        }

        return 9000;
    }

    public function importData(string $reportName, string $requestDataXml, string $context = 'generic', ?string $companyName = null): array
    {
        $reportName = trim($reportName);
        $staticVariables = '';
        $companyName = $this->resolveCompanyName($companyName);

        if (! empty($companyName)) {
            $staticVariables = '<STATICVARIABLES><SVCURRENTCOMPANY>'.$this->xmlEscape($companyName).'</SVCURRENTCOMPANY></STATICVARIABLES>';
        }

        $xmlEnvelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE>'
            .'<HEADER><TALLYREQUEST>Import Data</TALLYREQUEST></HEADER>'
            .'<BODY>'
            .'<IMPORTDATA>'
            .'<REQUESTDESC>'
            .'<REPORTNAME>'.$this->xmlEscape($reportName).'</REPORTNAME>'
            .$staticVariables
            .'</REQUESTDESC>'
            .'<REQUESTDATA>'.$requestDataXml.'</REQUESTDATA>'
            .'</IMPORTDATA>'
            .'</BODY>'
            .'</ENVELOPE>';

        return $this->sendEnvelope($xmlEnvelope, $context);
    }

    public function testXmlConnection(string $host, int $xmlPort = 9000, ?string $companyName = null): array
    {
        $staticVariables = '';
        $companyName = trim((string) ($companyName ?? ''));

        if ($companyName !== '') {
            $staticVariables = '<STATICVARIABLES><SVCURRENTCOMPANY>'.$this->xmlEscape($companyName).'</SVCURRENTCOMPANY></STATICVARIABLES>';
        }

        $xmlEnvelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE>'
            .'<HEADER><TALLYREQUEST>Export Data</TALLYREQUEST></HEADER>'
            .'<BODY>'
            .'<EXPORTDATA>'
            .'<REQUESTDESC>'
            .'<REPORTNAME>List of Companies</REPORTNAME>'
            .$staticVariables
            .'</REQUESTDESC>'
            .'</EXPORTDATA>'
            .'</BODY>'
            .'</ENVELOPE>';

        return $this->postXmlEnvelope(
            endpoint: $this->buildEndpoint($host, $xmlPort),
            xmlEnvelope: $xmlEnvelope,
            context: 'connection_test',
            expectImportCounters: false,
        );
    }

    public function sendEnvelope(string $xmlEnvelope, string $context = 'generic'): array
    {
        $settings = $this->activeSettings();

        if (! $settings || empty($settings->host)) {
            return [
                'status' => false,
                'message' => 'Tally connection settings are missing or inactive.',
                'request_xml' => $xmlEnvelope,
                'response_body' => null,
                'http_status' => null,
                'parsed' => [],
            ];
        }

        return $this->postXmlEnvelope(
            endpoint: $this->buildEndpoint((string) $settings->host, $this->resolvedXmlPort($settings)),
            xmlEnvelope: $xmlEnvelope,
            context: $context,
            expectImportCounters: true,
        );
    }

    private function postXmlEnvelope(string $endpoint, string $xmlEnvelope, string $context, bool $expectImportCounters): array
    {
        try {
            $response = Http::timeout(20)
                ->connectTimeout(7)
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Accept' => 'text/xml, application/xml, */*',
                ])
                ->withBody($xmlEnvelope, 'text/xml')
                ->post($endpoint);

            $body = (string) $response->body();
            $parsed = $this->parseTallyResponse($body);

            $hasTallyError = (($parsed['errors'] ?? 0) > 0)
                || (($parsed['exceptions'] ?? 0) > 0)
                || ! empty($parsed['line_errors']);
            $hasBody = trim($body) !== '';
            $hasExpectedResponse = $expectImportCounters
                ? (bool) ($parsed['has_import_counters'] ?? false)
                : (bool) ($parsed['has_tally_response'] ?? false);

            $status = $response->successful()
                && $hasBody
                && $hasExpectedResponse
                && ! $hasTallyError;

            if (! $response->successful()) {
                $message = 'Tally XML server returned HTTP '.$response->status().': '.$this->bodyExcerpt($body);
            } elseif (! $hasBody) {
                $message = 'Tally XML server returned an empty response.';
            } elseif ($hasTallyError) {
                $message = 'Tally error: '.($parsed['message'] ?: $this->bodyExcerpt($body));
            } elseif (! $hasExpectedResponse) {
                $message = 'Unexpected response from Tally XML server: '.$this->bodyExcerpt($body);
            } else {
                $message = 'Tally request successful. '.$parsed['message'];
            }

            if (! $status) {
                Log::warning('Tally request failed', [
                    'context' => $context,
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'parsed' => $parsed,
                    'response_excerpt' => $this->bodyExcerpt($body),
                ]);
            }

            return [
                'status' => $status,
                'message' => $message,
                'request_xml' => $xmlEnvelope,
                'response_body' => $body,
                'http_status' => $response->status(),
                'endpoint' => $endpoint,
                'parsed' => $parsed,
            ];
        } catch (\Throwable $e) {
            Log::error('Tally request exception', [
                'context' => $context,
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Tally request exception: '.$e->getMessage(),
                'request_xml' => $xmlEnvelope,
                'response_body' => null,
                'http_status' => null,
                'endpoint' => $endpoint,
                'parsed' => [],
            ];
        }
    }

    private function parseTallyResponse(string $xmlBody): array
    {
        $body = trim($xmlBody);
        $created = $this->extractTagValue($xmlBody, 'CREATED');
        $altered = $this->extractTagValue($xmlBody, 'ALTERED');
        $deleted = $this->extractTagValue($xmlBody, 'DELETED');
        $cancelled = $this->extractTagValue($xmlBody, 'CANCELLED');
        $errors = $this->extractTagValue($xmlBody, 'ERRORS');
        $exceptions = $this->extractTagValue($xmlBody, 'EXCEPTIONS');
        $lastVoucherId = $this->extractTagText($xmlBody, 'LASTVCHID');
        $lineErrors = array_values(array_unique(array_merge(
            $this->extractTagList($xmlBody, 'LINEERROR'),
            $this->extractTagList($xmlBody, 'ERRDESC'),
            $this->extractTagList($xmlBody, 'ERROR')
        )));
        $hasImportCounters = preg_match('/<(CREATED|ALTERED|DELETED|CANCELLED|ERRORS|EXCEPTIONS)>/i', $xmlBody) === 1;
        $looksLikeXml = $body !== '' && str_starts_with($body, '<') && str_contains($body, '>');
        $hasTallyResponse = $looksLikeXml && preg_match('/<(ENVELOPE|RESPONSE|IMPORTRESULT|CREATED|ALTERED|ERRORS|LINEERROR|DSPACCNAME|COMPANY)>/i', $xmlBody) === 1;

        $message = '';
        if (! empty($lineErrors)) {
            $message = implode(' | ', $lineErrors);
        } elseif ($errors > 0 || $exceptions > 0) {
            $message = 'Tally returned import errors. Errors: '.$errors.', Exceptions: '.$exceptions.'. '.$this->bodyExcerpt($xmlBody);
        } elseif ($hasImportCounters) {
            $message = "Created: {$created}, Altered: {$altered}, Deleted: {$deleted}, Cancelled: {$cancelled}";
            if ($lastVoucherId !== '') {
                $message .= ", Last Voucher ID: {$lastVoucherId}";
            }
        } elseif ($hasTallyResponse) {
            $message = 'Tally XML server responded.';
        } else {
            $message = '';
        }

        return [
            'created' => $created,
            'altered' => $altered,
            'deleted' => $deleted,
            'cancelled' => $cancelled,
            'errors' => $errors,
            'exceptions' => $exceptions,
            'last_voucher_id' => $lastVoucherId,
            'line_errors' => $lineErrors,
            'message' => $message,
            'has_import_counters' => $hasImportCounters,
            'has_tally_response' => $hasTallyResponse,
            'looks_like_xml' => $looksLikeXml,
        ];
    }

    private function extractTagValue(string $xml, string $tag): int
    {
        if (preg_match('/<'.preg_quote($tag, '/').'>\s*(-?\d+)\s*<\/'.preg_quote($tag, '/').'>/i', $xml, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractTagList(string $xml, string $tag): array
    {
        if (! preg_match_all('/<'.preg_quote($tag, '/').'>\s*(.*?)\s*<\/'.preg_quote($tag, '/').'>/is', $xml, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($line) {
            return trim(html_entity_decode(strip_tags((string) $line)));
        }, $matches[1])));
    }

    private function extractTagText(string $xml, string $tag): string
    {
        if (preg_match('/<'.preg_quote($tag, '/').'>\s*(.*?)\s*<\/'.preg_quote($tag, '/').'>/is', $xml, $matches)) {
            return trim(html_entity_decode(strip_tags((string) $matches[1])));
        }

        return '';
    }

    private function buildEndpoint(string $host, int $port): string
    {
        $host = trim($host);
        if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
            $host = preg_replace('/:\d+$/', '', rtrim($host, '/')) ?: $host;

            return $host.':'.$port;
        }

        return 'http://'.$host.':'.$port;
    }

    private function resolveCompanyName(?string $companyName): ?string
    {
        $companyName = trim((string) ($companyName ?? ''));

        return $companyName !== '' ? $companyName : $this->defaultCompanyName();
    }

    private function settingString(?TallyIntegrationSetting $settings, string $attribute): string
    {
        return trim((string) ($settings?->getAttribute($attribute) ?? ''));
    }

    private function bodyExcerpt(string $body): string
    {
        $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');

        if ($excerpt === '') {
            $excerpt = trim(preg_replace('/\s+/', ' ', $body) ?? '');
        }

        return mb_substr($excerpt, 0, 500);
    }

    public function xmlEscape(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
