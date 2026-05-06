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

    public function settingValue(string $attribute, string $fallback = ''): string
    {
        $value = $this->settingString($this->activeSettings(), $attribute);

        return $value !== '' ? $value : $fallback;
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
        $staticVariables = '<SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT>';
        $companyName = trim((string) ($companyName ?? ''));

        if ($companyName !== '') {
            $staticVariables .= '<SVCURRENTCOMPANY>'.$this->xmlEscape($companyName).'</SVCURRENTCOMPANY>';
        }

        $xmlEnvelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE>'
            .'<HEADER><TALLYREQUEST>Export Data</TALLYREQUEST></HEADER>'
            .'<BODY>'
            .'<EXPORTDATA>'
            .'<REQUESTDESC>'
            .'<REPORTNAME>Collection</REPORTNAME>'
            .'<STATICVARIABLES>'.$staticVariables.'</STATICVARIABLES>'
            .'</REQUESTDESC>'
            .'<REQUESTDATA>'
            .'<TDL>'
            .'<TDLMESSAGE>'
            .'<COLLECTION NAME="Open Companies" ISMODIFY="No">'
            .'<TYPE>Company</TYPE>'
            .'<FETCH>Name</FETCH>'
            .'</COLLECTION>'
            .'</TDLMESSAGE>'
            .'</TDL>'
            .'</REQUESTDATA>'
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

    public function fetchCurrentCompany(?string $host = null, ?int $xmlPort = null): array
    {
        $xmlEnvelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE>'
            .'<HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Collection</TYPE><ID>CompanyInfo</ID></HEADER>'
            .'<BODY><DESC>'
            .'<STATICVARIABLES><SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT></STATICVARIABLES>'
            .'<TDL><TDLMESSAGE>'
            .'<OBJECT NAME="CurrentCompany"><LOCALFORMULA>CurrentCompany:##SVCURRENTCOMPANY</LOCALFORMULA></OBJECT>'
            .'<COLLECTION NAME="CompanyInfo"><OBJECTS>CurrentCompany</OBJECTS><NATIVEMETHOD>CurrentCompany</NATIVEMETHOD></COLLECTION>'
            .'</TDLMESSAGE></TDL>'
            .'</DESC></BODY>'
            .'</ENVELOPE>';

        $result = $this->sendExportEnvelope($xmlEnvelope, 'fetch_current_company', $host, $xmlPort);
        $companyName = ($result['status'] ?? false) ? $this->parseCurrentCompanyName((string) ($result['response_body'] ?? '')) : '';

        $result['current_company'] = $companyName;
        $result['data'] = $companyName !== '' ? [['name' => $companyName]] : [];
        $result['count'] = count($result['data']);

        if (($result['status'] ?? false) && $companyName !== '') {
            $result['message'] = 'Current Tally company fetched successfully.';
        }

        return $result;
    }

    public function fetchLedgers(?string $companyName = null, ?string $host = null, ?int $xmlPort = null): array
    {
        return $this->fetchMasterCollection(
            collectionName: 'Ledgers',
            masterType: 'Ledger',
            nativeMethods: ['Name', 'Parent', 'MasterID', 'GUID', 'OpeningBalance', 'ClosingBalance', 'PartyGSTIN', 'LedgerMobile', 'Email'],
            companyName: $companyName,
            host: $host,
            xmlPort: $xmlPort,
            context: 'fetch_ledgers',
        );
    }

    public function fetchGroups(?string $companyName = null, ?string $host = null, ?int $xmlPort = null): array
    {
        return $this->fetchMasterCollection(
            collectionName: 'Groups',
            masterType: 'Group',
            nativeMethods: ['Name', 'Parent', 'MasterID', 'GUID'],
            companyName: $companyName,
            host: $host,
            xmlPort: $xmlPort,
            context: 'fetch_groups',
        );
    }

    public function fetchStockItems(?string $companyName = null, ?string $host = null, ?int $xmlPort = null): array
    {
        return $this->fetchMasterCollection(
            collectionName: 'StockItems',
            masterType: 'StockItem',
            nativeMethods: ['Name', 'Parent', 'MasterID', 'GUID', 'BaseUnits', 'HSNCode', 'ClosingBalance', 'ClosingRate', 'ClosingValue'],
            companyName: $companyName,
            host: $host,
            xmlPort: $xmlPort,
            context: 'fetch_stock_items',
        );
    }

    public function fetchUnits(?string $companyName = null, ?string $host = null, ?int $xmlPort = null): array
    {
        return $this->fetchMasterCollection(
            collectionName: 'Units',
            masterType: 'Unit',
            nativeMethods: ['Name', 'OriginalName', 'MasterID', 'GUID', 'DecimalPlaces', 'IsSimpleUnit'],
            companyName: $companyName,
            host: $host,
            xmlPort: $xmlPort,
            context: 'fetch_units',
        );
    }

    public function fetchVoucherTypes(?string $companyName = null, ?string $host = null, ?int $xmlPort = null): array
    {
        return $this->fetchMasterCollection(
            collectionName: 'VoucherTypes',
            masterType: 'VoucherType',
            nativeMethods: ['Name', 'Parent', 'MasterID', 'GUID', 'NumberingMethod'],
            companyName: $companyName,
            host: $host,
            xmlPort: $xmlPort,
            context: 'fetch_voucher_types',
        );
    }

    public function fetchMasterOptions(?string $companyName = null, ?string $host = null, ?int $xmlPort = null): array
    {
        $currentCompany = $this->fetchCurrentCompany($host, $xmlPort);
        $resolvedCompanyName = trim((string) (($currentCompany['current_company'] ?? '') ?: ($companyName ?: ($this->defaultCompanyName() ?? ''))));

        $ledgers = $this->fetchLedgers($resolvedCompanyName, $host, $xmlPort);
        $groups = $this->fetchGroups($resolvedCompanyName, $host, $xmlPort);
        $stockItems = $this->fetchStockItems($resolvedCompanyName, $host, $xmlPort);
        $units = $this->fetchUnits($resolvedCompanyName, $host, $xmlPort);
        $voucherTypes = $this->fetchVoucherTypes($resolvedCompanyName, $host, $xmlPort);

        $results = [
            'current_company' => $currentCompany,
            'ledgers' => $ledgers,
            'groups' => $groups,
            'stock_items' => $stockItems,
            'units' => $units,
            'voucher_types' => $voucherTypes,
        ];

        $failed = array_filter($results, static fn (array $result) => ! (bool) ($result['status'] ?? false));
        $messages = array_values(array_filter(array_map(static fn (array $result) => $result['message'] ?? '', $failed)));

        return [
            'status' => count($failed) === 0,
            'message' => count($failed) === 0
                ? 'Tally company and master options fetched successfully.'
                : 'Some Tally master options could not be fetched: '.implode(' | ', $messages),
            'current_company' => $resolvedCompanyName,
            'companies' => $currentCompany['data'] ?? [],
            'ledgers' => $ledgers['data'] ?? [],
            'groups' => $groups['data'] ?? [],
            'stock_items' => $stockItems['data'] ?? [],
            'units' => $units['data'] ?? [],
            'voucher_types' => $voucherTypes['data'] ?? [],
            'field_options' => $this->tallyFieldOptions(),
            'counts' => [
                'companies' => count($currentCompany['data'] ?? []),
                'ledgers' => count($ledgers['data'] ?? []),
                'groups' => count($groups['data'] ?? []),
                'stock_items' => count($stockItems['data'] ?? []),
                'units' => count($units['data'] ?? []),
                'voucher_types' => count($voucherTypes['data'] ?? []),
            ],
            'diagnostics' => array_map(fn (array $result) => $this->exportDiagnostics($result), $results),
        ];
    }

    public function tallyFieldOptions(): array
    {
        return [
            'ledger' => [
                'NAME',
                'PARENT',
                'PARTYGSTIN',
                'LEDGERMOBILE',
                'EMAIL',
                'OPENINGBALANCE',
                'CLOSINGBALANCE',
            ],
            'stock_item' => [
                'NAME',
                'PARENT',
                'BASEUNITS',
                'HSNCODE',
                'OPENINGBALANCE',
                'CLOSINGBALANCE',
                'CLOSINGRATE',
                'CLOSINGVALUE',
            ],
            'voucher' => [
                'VOUCHERNUMBER',
                'DATE',
                'VOUCHERTYPENAME',
                'PARTYLEDGERNAME',
                'REFERENCE',
                'NARRATION',
                'AMOUNT',
                'LEDGERNAME',
                'STOCKITEMNAME',
                'RATE',
                'ACTUALQTY',
                'BILLEDQTY',
            ],
        ];
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
                || (($parsed['response_status'] ?? null) === 0)
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
            } elseif ($context === 'connection_test' && $hasBody && ($parsed['has_tally_response'] ?? false)) {
                $status = true;
                $message = empty($parsed['line_errors'])
                    ? 'Tally XML server is reachable. '.$parsed['message']
                    : 'Tally XML server is reachable. Diagnostic response: '.$parsed['message'];
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
        $responseStatusText = $this->extractTagText($xmlBody, 'STATUS');
        $responseStatus = is_numeric($responseStatusText) ? (int) $responseStatusText : null;
        $hasImportCounters = preg_match('/<(CREATED|ALTERED|DELETED|CANCELLED|ERRORS|EXCEPTIONS)>/i', $xmlBody) === 1;
        $looksLikeXml = $body !== '' && str_starts_with($body, '<') && str_contains($body, '>');
        $hasTallyResponse = $looksLikeXml && preg_match('/<(ENVELOPE|RESPONSE|IMPORTRESULT|CREATED|ALTERED|ERRORS|LINEERROR|DSPACCNAME|COMPANY)>/i', $xmlBody) === 1;

        $message = '';
        if (! empty($lineErrors)) {
            $message = implode(' | ', $lineErrors);
        } elseif ($responseStatus === 0) {
            $message = 'Tally returned response status 0.';
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
            'response_status' => $responseStatus,
            'line_errors' => $lineErrors,
            'message' => $message,
            'has_import_counters' => $hasImportCounters,
            'has_tally_response' => $hasTallyResponse,
            'looks_like_xml' => $looksLikeXml,
        ];
    }

    private function extractTagValue(string $xml, string $tag): int
    {
        if (preg_match('/<'.preg_quote($tag, '/').'\b[^>]*>\s*(-?\d+)\s*<\/'.preg_quote($tag, '/').'>/i', $xml, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractTagList(string $xml, string $tag): array
    {
        if (! preg_match_all('/<'.preg_quote($tag, '/').'\b[^>]*>\s*(.*?)\s*<\/'.preg_quote($tag, '/').'>/is', $xml, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($line) {
            return trim(html_entity_decode(strip_tags((string) $line)));
        }, $matches[1])));
    }

    private function extractTagText(string $xml, string $tag): string
    {
        if (preg_match('/<'.preg_quote($tag, '/').'\b[^>]*>\s*(.*?)\s*<\/'.preg_quote($tag, '/').'>/is', $xml, $matches)) {
            return trim(html_entity_decode(strip_tags((string) $matches[1])));
        }

        return '';
    }

    private function fetchMasterCollection(string $collectionName, string $masterType, array $nativeMethods, ?string $companyName, ?string $host, ?int $xmlPort, string $context): array
    {
        $xmlEnvelope = $this->collectionExportEnvelope($collectionName, $masterType, $nativeMethods, $companyName);
        $result = $this->sendExportEnvelope($xmlEnvelope, $context, $host, $xmlPort);
        $records = ($result['status'] ?? false) ? $this->parseCollectionRecords((string) ($result['response_body'] ?? ''), $masterType) : [];

        $result['data'] = $records;
        $result['count'] = count($records);

        if (($result['status'] ?? false)) {
            $result['message'] = ucfirst(str_replace('_', ' ', $context)).' fetched successfully.';
        }

        return $result;
    }

    private function collectionExportEnvelope(string $collectionName, string $masterType, array $nativeMethods, ?string $companyName): string
    {
        $companyName = $this->resolveCompanyName($companyName);
        $staticVariables = '<SVEXPORTFORMAT>$$SysName:XML</SVEXPORTFORMAT>';

        if (! empty($companyName)) {
            $staticVariables .= '<SVCURRENTCOMPANY>'.$this->xmlEscape($companyName).'</SVCURRENTCOMPANY>';
        }

        $fetchXml = '';
        foreach ($nativeMethods as $method) {
            $fetchXml .= '<NATIVEMETHOD>'.$this->xmlEscape($method).'</NATIVEMETHOD>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<ENVELOPE>'
            .'<HEADER><VERSION>1</VERSION><TALLYREQUEST>Export</TALLYREQUEST><TYPE>Collection</TYPE><ID>'.$this->xmlEscape($collectionName).'</ID></HEADER>'
            .'<BODY><DESC>'
            .'<STATICVARIABLES>'.$staticVariables.'</STATICVARIABLES>'
            .'<TDL><TDLMESSAGE>'
            .'<COLLECTION NAME="'.$this->xmlEscape($collectionName).'" ISMODIFY="No" ISFIXED="No" ISINITIALIZE="No" ISOPTION="No" ISINTERNAL="No">'
            .'<TYPE>'.$this->xmlEscape($masterType).'</TYPE>'
            .$fetchXml
            .'</COLLECTION>'
            .'</TDLMESSAGE></TDL>'
            .'</DESC></BODY>'
            .'</ENVELOPE>';
    }

    private function sendExportEnvelope(string $xmlEnvelope, string $context, ?string $host = null, ?int $xmlPort = null): array
    {
        $settings = $this->activeSettings();
        $host = trim((string) ($host ?: $settings?->host ?: ''));
        $xmlPort = (int) ($xmlPort ?: $this->resolvedXmlPort($settings));

        if ($host === '') {
            return [
                'status' => false,
                'message' => 'Tally host is missing. Save Tally connection settings first or pass host/xml_port.',
                'request_xml' => $xmlEnvelope,
                'response_body' => null,
                'http_status' => null,
                'endpoint' => null,
                'parsed' => [],
            ];
        }

        return $this->postXmlEnvelope(
            endpoint: $this->buildEndpoint($host, $xmlPort),
            xmlEnvelope: $xmlEnvelope,
            context: $context,
            expectImportCounters: false,
        );
    }

    private function parseCurrentCompanyName(string $xmlBody): string
    {
        $companyName = '';
        $xml = $this->loadXml($xmlBody);

        if ($xml) {
            foreach ($xml->xpath('//*[local-name()="CURRENTCOMPANY"]') ?: [] as $node) {
                $text = $this->cleanScalar((string) $node);
                if ($text !== '') {
                    $companyName = $text;
                }
            }
        }

        if ($companyName === '') {
            $companyName = $this->extractTagText($xmlBody, 'CURRENTCOMPANY');
        }

        return $companyName;
    }

    private function parseCollectionRecords(string $xmlBody, string $masterType): array
    {
        $xml = $this->loadXml($xmlBody);
        if (! $xml) {
            return [];
        }

        $records = [];
        $target = strtoupper($masterType);
        $walk = function (\SimpleXMLElement $node) use (&$walk, &$records, $target): void {
            if (strtoupper($node->getName()) === strtoupper($target)) {
                $record = $this->normalizeMasterNode($node);
                if (($record['name'] ?? '') !== '') {
                    $records[] = $record;
                }
            }

            foreach ($node->children() as $child) {
                $walk($child);
            }
        };

        $walk($xml);

        $unique = [];
        foreach ($records as $record) {
            $key = strtolower(($record['name'] ?? '').'|'.($record['parent'] ?? '').'|'.($record['master_id'] ?? ''));
            $unique[$key] = $record;
        }

        $records = array_values($unique);
        usort($records, static fn (array $a, array $b) => strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        return $records;
    }

    private function normalizeMasterNode(\SimpleXMLElement $node): array
    {
        $attributes = [];
        foreach ($node->attributes() as $key => $value) {
            $attributes[strtolower((string) $key)] = $this->cleanScalar((string) $value);
        }

        $fields = [];
        foreach ($node->children() as $child) {
            $key = strtoupper($child->getName());
            $value = $this->cleanScalar((string) $child);
            if ($value === '') {
                continue;
            }

            if (array_key_exists($key, $fields)) {
                if (! is_array($fields[$key])) {
                    $fields[$key] = [$fields[$key]];
                }
                $fields[$key][] = $value;
            } else {
                $fields[$key] = $value;
            }
        }

        $name = $this->fieldValue($fields, 'NAME') ?: ($attributes['name'] ?? '');

        return [
            'name' => $name,
            'parent' => $this->fieldValue($fields, 'PARENT'),
            'master_id' => $this->fieldValue($fields, 'MASTERID'),
            'guid' => $this->fieldValue($fields, 'GUID'),
            'reserved_name' => $attributes['reservedname'] ?? '',
            'fields' => $fields,
        ];
    }

    private function fieldValue(array $fields, string $key): string
    {
        $value = $fields[strtoupper($key)] ?? '';

        return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
    }

    private function loadXml(string $xmlBody): ?\SimpleXMLElement
    {
        if (! function_exists('simplexml_load_string')) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($xmlBody, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);

            return $xml instanceof \SimpleXMLElement ? $xml : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function cleanScalar(string $value): string
    {
        return trim(html_entity_decode(preg_replace('/\s+/', ' ', $value) ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }

    private function exportDiagnostics(array $result): array
    {
        return [
            'status' => (bool) ($result['status'] ?? false),
            'message' => $result['message'] ?? '',
            'count' => $result['count'] ?? 0,
            'endpoint' => $result['endpoint'] ?? null,
            'http_status' => $result['http_status'] ?? null,
            'tally_response' => $result['parsed'] ?? [],
        ];
    }

    private function buildEndpoint(string $host, int $port): string
    {
        $host = trim($host);
        $host = preg_replace('/\s+/', '', $host) ?: $host;
        if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
            $parts = parse_url($host);
            $scheme = $parts['scheme'] ?? 'http';
            $hostname = $parts['host'] ?? '';

            if ($hostname !== '') {
                return $scheme.'://'.$hostname.':'.$port;
            }

            $host = preg_replace('/:\d+$/', '', rtrim($host, '/')) ?: $host;

            return $host.':'.$port;
        }

        $host = preg_replace('/:\d+$/', '', $host) ?: $host;

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
