<?php

namespace App\Services\Gst;

use App\Models\Sale\Sale;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

class EInvoiceApiService
{
    public function isEnabled(): bool
    {
        return ! empty(config('services.einvoice.auth_url'))
            && ! empty(config('services.einvoice.generate_url'));
    }

    public function authenticate(): array
    {
        try {
            $url = config('services.einvoice.auth_url');
            if (empty($url)) {
                return ['ok' => false, 'message' => 'eInvoice auth URL is not configured.'];
            }

            $payload = array_filter([
                'username' => config('services.einvoice.username'),
                'password' => config('services.einvoice.password'),
                'gstin' => config('services.einvoice.gstin'),
            ], fn ($value) => ! is_null($value) && $value !== '');

            $response = Http::timeout((int) config('services.einvoice.request_timeout', 30))
                ->withHeaders($this->baseHeaders())
                ->post($url, $payload);

            $json = $response->json() ?? [];
            $token = $this->extractToken($json);

            if (! $response->successful() || empty($token)) {
                return [
                    'ok' => false,
                    'message' => $this->extractMessage($json, 'Failed to authenticate with eInvoice API.'),
                    'raw' => $json,
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'token' => $token,
                'raw' => $json,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function generateIrnForSale(Sale $sale): array
    {
        try {
            $auth = $this->authenticate();
            if (! $auth['ok']) {
                return $auth;
            }

            $url = config('services.einvoice.generate_url');
            if (empty($url)) {
                return ['ok' => false, 'message' => 'eInvoice generate URL is not configured.'];
            }

            $sale->loadMissing(['party', 'itemTransaction.item.tax', 'itemTransaction.tax']);
            $payload = $this->buildInvoicePayload($sale);

            $response = Http::timeout((int) config('services.einvoice.request_timeout', 30))
                ->withHeaders($this->authHeaders($auth['token']))
                ->post($url, $payload);

            $json = $response->json() ?? [];

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractMessage($json, 'Failed to generate IRN.'),
                    'raw' => $json,
                    'payload' => $payload,
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'message' => $this->extractMessage($json, 'IRN generated successfully.'),
                'parsed' => $this->extractEInvoiceFields($json),
                'raw' => $json,
                'payload' => $payload,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function cancelIrn(string $irn, string $reason, string $remarks = ''): array
    {
        try {
            $auth = $this->authenticate();
            if (! $auth['ok']) {
                return $auth;
            }

            $url = config('services.einvoice.cancel_url');
            if (empty($url)) {
                return ['ok' => false, 'message' => 'eInvoice cancel URL is not configured.'];
            }

            $payload = [
                'Irn' => $irn,
                'CnlRsn' => $reason,
                'CnlRem' => $remarks,
            ];

            $response = Http::timeout((int) config('services.einvoice.request_timeout', 30))
                ->withHeaders($this->authHeaders($auth['token']))
                ->post($url, $payload);

            $json = $response->json() ?? [];

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractMessage($json, 'Failed to cancel IRN.'),
                    'raw' => $json,
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'message' => $this->extractMessage($json, 'IRN cancelled successfully.'),
                'raw' => $json,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function generateEwbByIrn(string $irn, array $transportPayload = []): array
    {
        try {
            $auth = $this->authenticate();
            if (! $auth['ok']) {
                return $auth;
            }

            $url = config('services.einvoice.generate_ewb_by_irn_url');
            if (empty($url)) {
                return ['ok' => false, 'message' => 'eInvoice generate EWB URL is not configured.'];
            }

            $payload = array_merge(['Irn' => $irn], $transportPayload);

            $response = Http::timeout((int) config('services.einvoice.request_timeout', 30))
                ->withHeaders($this->authHeaders($auth['token']))
                ->post($url, $payload);

            $json = $response->json() ?? [];
            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractMessage($json, 'Failed to generate eWay bill.'),
                    'raw' => $json,
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'message' => $this->extractMessage($json, 'eWay bill generated successfully.'),
                'parsed' => $this->extractEInvoiceFields($json),
                'raw' => $json,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function cancelEwb(string $ewbNo, string $reasonCode, string $reasonRemark = ''): array
    {
        try {
            $auth = $this->authenticate();
            if (! $auth['ok']) {
                return $auth;
            }

            $url = config('services.ewaybill.cancel_url') ?: config('services.ewaybill.action_url');
            if (empty($url)) {
                return ['ok' => false, 'message' => 'eWay bill cancel URL is not configured.'];
            }

            $payload = [
                'action' => 'CANEWB',
                'ewbNo' => $ewbNo,
                'cancelRsnCode' => $reasonCode,
                'cancelRmrk' => $reasonRemark,
            ];

            $response = Http::timeout((int) config('services.einvoice.request_timeout', 30))
                ->withHeaders($this->authHeaders($auth['token']))
                ->post($url, $payload);

            $json = $response->json() ?? [];
            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractMessage($json, 'Failed to cancel eWay bill.'),
                    'raw' => $json,
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'message' => $this->extractMessage($json, 'eWay bill cancelled successfully.'),
                'raw' => $json,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function getByIrn(string $irn): array
    {
        try {
            $auth = $this->authenticate();
            if (! $auth['ok']) {
                return $auth;
            }

            $url = config('services.einvoice.get_by_irn_url');
            if (empty($url)) {
                return ['ok' => false, 'message' => 'eInvoice get-by-IRN URL is not configured.'];
            }

            $response = Http::timeout((int) config('services.einvoice.request_timeout', 30))
                ->withHeaders($this->authHeaders($auth['token']))
                ->post($url, ['Irn' => $irn]);

            $json = $response->json() ?? [];
            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => $this->extractMessage($json, 'Failed to fetch eInvoice by IRN.'),
                    'raw' => $json,
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => true,
                'message' => $this->extractMessage($json, 'Fetched eInvoice details successfully.'),
                'parsed' => $this->extractEInvoiceFields($json),
                'raw' => $json,
                'status_code' => $response->status(),
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function buildInvoicePayload(Sale $sale): array
    {
        $company = app('company');
        $party = $sale->party;
        $itemList = [];
        $assVal = 0;
        $cgstVal = 0;
        $sgstVal = 0;
        $igstVal = 0;

        foreach ($sale->itemTransaction as $index => $item) {
            $lineTax = (float) ($item->tax_amount ?? 0);
            $lineTaxRate = (float) ($item->tax?->rate ?? $item->item?->tax?->rate ?? 0);
            $lineAmount = (float) ($item->total ?? 0);
            $lineAssessable = max($lineAmount - $lineTax, 0);
            $isIntraState = empty($sale->state_id) || (string) $company['state_id'] === (string) $sale->state_id;

            $itemList[] = [
                'SlNo' => (string) ($index + 1),
                'PrdDesc' => (string) ($item->item?->name ?? 'Item'),
                'IsServc' => 'N',
                'HsnCd' => (string) ($item->item?->hsn ?? ''),
                'Qty' => (float) ($item->quantity ?? 0),
                'Unit' => strtoupper(substr((string) ($item->unit?->name ?? 'NOS'), 0, 3)),
                'UnitPrice' => (float) ($item->unit_price ?? 0),
                'TotAmt' => round($lineAssessable, 2),
                'Discount' => (float) ($item->discount_amount ?? 0),
                'AssAmt' => round($lineAssessable, 2),
                'GstRt' => $lineTaxRate,
                'IgstAmt' => $isIntraState ? 0 : round($lineTax, 2),
                'CgstAmt' => $isIntraState ? round($lineTax / 2, 2) : 0,
                'SgstAmt' => $isIntraState ? round($lineTax / 2, 2) : 0,
                'TotItemVal' => round($lineAmount, 2),
            ];

            $assVal += $lineAssessable;
            if ($isIntraState) {
                $cgstVal += $lineTax / 2;
                $sgstVal += $lineTax / 2;
            } else {
                $igstVal += $lineTax;
            }
        }

        return [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => 'B2B',
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => (string) $sale->sale_code,
                'Dt' => date('d/m/Y', strtotime((string) $sale->sale_date)),
            ],
            'SellerDtls' => [
                'Gstin' => (string) (config('services.einvoice.gstin') ?: ($company['tax_number'] ?? '')),
                'LglNm' => (string) ($company['name'] ?? ''),
                'Addr1' => (string) ($company['address'] ?? ''),
                'Loc' => (string) ($company['city'] ?? ''),
                'Pin' => (int) ($company['pincode'] ?? 0),
                'Stcd' => (string) ($company['state_code'] ?? ''),
            ],
            'BuyerDtls' => [
                'Gstin' => (string) ($party?->company_gst ?? ''),
                'LglNm' => (string) ($party?->company_name ?? ''),
                'Pos' => (string) ($party?->state?->state_code ?? ''),
                'Addr1' => (string) ($party?->billing_address ?? ''),
                'Loc' => (string) ($party?->city ?? ''),
                'Pin' => (int) ($party?->pin_code ?? 0),
                'Stcd' => (string) ($party?->state?->state_code ?? ''),
            ],
            'ItemList' => $itemList,
            'ValDtls' => [
                'AssVal' => round($assVal, 2),
                'CgstVal' => round($cgstVal, 2),
                'SgstVal' => round($sgstVal, 2),
                'IgstVal' => round($igstVal, 2),
                'TotInvVal' => (float) $sale->grand_total,
            ],
        ];
    }

    public function extractEInvoiceFields(array $json): array
    {
        return [
            'irn' => Arr::get($json, 'Irn')
                ?? Arr::get($json, 'data.Irn')
                ?? Arr::get($json, 'result.Irn'),
            'irn_ack_no' => Arr::get($json, 'AckNo')
                ?? Arr::get($json, 'data.AckNo')
                ?? Arr::get($json, 'result.AckNo'),
            'irn_ack_date' => Arr::get($json, 'AckDt')
                ?? Arr::get($json, 'data.AckDt')
                ?? Arr::get($json, 'result.AckDt'),
            'irn_signed_qr_code' => Arr::get($json, 'SignedQRCode')
                ?? Arr::get($json, 'data.SignedQRCode')
                ?? Arr::get($json, 'result.SignedQRCode'),
            'ewb_no' => Arr::get($json, 'EwbNo')
                ?? Arr::get($json, 'data.EwbNo')
                ?? Arr::get($json, 'result.EwbNo'),
            'ewb_date' => Arr::get($json, 'EwbDt')
                ?? Arr::get($json, 'data.EwbDt')
                ?? Arr::get($json, 'result.EwbDt'),
            'ewb_valid_till' => Arr::get($json, 'EwbValidTill')
                ?? Arr::get($json, 'data.EwbValidTill')
                ?? Arr::get($json, 'result.EwbValidTill'),
        ];
    }

    protected function extractToken(array $json): ?string
    {
        return Arr::get($json, 'token')
            ?? Arr::get($json, 'access_token')
            ?? Arr::get($json, 'AuthToken')
            ?? Arr::get($json, 'data.token')
            ?? Arr::get($json, 'data.access_token')
            ?? Arr::get($json, 'data.AuthToken')
            ?? Arr::get($json, 'result.token')
            ?? Arr::get($json, 'result.AuthToken');
    }

    protected function extractMessage(array $json, string $default): string
    {
        return Arr::get($json, 'message')
            ?? Arr::get($json, 'Message')
            ?? Arr::get($json, 'error.message')
            ?? Arr::get($json, 'error')
            ?? Arr::get($json, 'ErrorMessage')
            ?? $default;
    }

    protected function baseHeaders(): array
    {
        return array_filter([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'client_id' => config('services.einvoice.client_id'),
            'client_secret' => config('services.einvoice.client_secret'),
            'gstin' => config('services.einvoice.gstin'),
            'username' => config('services.einvoice.username'),
        ], fn ($value) => ! is_null($value) && $value !== '');
    }

    protected function authHeaders(string $token): array
    {
        return array_merge($this->baseHeaders(), [
            'Authorization' => 'Bearer '.$token,
            'authtoken' => $token,
        ]);
    }
}

