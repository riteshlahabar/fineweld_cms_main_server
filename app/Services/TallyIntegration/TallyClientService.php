<?php

namespace App\Services\TallyIntegration;

use App\Models\Company;
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

    public function importData(string $reportName, string $requestDataXml, string $context = 'generic', ?string $companyName = null): array
    {
        $reportName = trim($reportName);
        $staticVariables = '';
        $resolvedCompanyName = trim((string) ($companyName ?? ''));

        if ($resolvedCompanyName === '') {
            $settings = $this->activeSettings();
            $resolvedCompanyName = trim((string) (($settings->company_name ?? null) ?: ($settings->username ?? null) ?: ''));

            if ($resolvedCompanyName === '') {
                $resolvedCompanyName = trim((string) (Company::query()->where('id', 1)->value('name') ?? ''));
            }
        }

        if ($resolvedCompanyName !== '') {
            $staticVariables = '<STATICVARIABLES><SVCURRENTCOMPANY>'.$this->xmlEscape($resolvedCompanyName).'</SVCURRENTCOMPANY></STATICVARIABLES>';
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

        $port = (int) ($settings->odbc_port ?: $settings->port ?: 9000);
        $endpoint = 'http://'.trim((string) $settings->host).':'.$port;

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

            $status = $response->successful() && (($parsed['errors'] ?? 0) === 0) && empty($parsed['line_errors']);
            $message = $status
                ? 'Tally request successful.'
                : ('Tally request failed. '.($parsed['message'] ?? 'Unknown error from Tally.'));

            if (! $status) {
                Log::warning('Tally request failed', [
                    'context' => $context,
                    'endpoint' => $endpoint,
                    'http_status' => $response->status(),
                    'parsed' => $parsed,
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
        $created = $this->extractTagValue($xmlBody, 'CREATED');
        $altered = $this->extractTagValue($xmlBody, 'ALTERED');
        $deleted = $this->extractTagValue($xmlBody, 'DELETED');
        $errors = $this->extractTagValue($xmlBody, 'ERRORS');
        $lineErrors = $this->extractTagList($xmlBody, 'LINEERROR');

        $message = '';
        if (! empty($lineErrors)) {
            $message = implode(' | ', $lineErrors);
        } elseif ($errors > 0) {
            $message = 'Tally returned import errors.';
        } else {
            $message = "Created: {$created}, Altered: {$altered}, Deleted: {$deleted}";
        }

        return [
            'created' => $created,
            'altered' => $altered,
            'deleted' => $deleted,
            'errors' => $errors,
            'line_errors' => $lineErrors,
            'message' => $message,
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

    public function xmlEscape(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
