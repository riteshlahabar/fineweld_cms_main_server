<?php

namespace App\Http\Controllers;

use App\Models\Sale\Sale;
use App\Services\Gst\EInvoiceApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EInvoiceController extends Controller
{
    public function __construct(private EInvoiceApiService $eInvoiceApiService) {}

    public function generateIrn(int $id): RedirectResponse
    {
        $sale = Sale::findOrFail($id);
        $result = $this->eInvoiceApiService->generateIrnForSale($sale);

        if (! $result['ok']) {
            $this->markFailure($sale, $result['message'] ?? 'IRN generation failed.');

            return back();
        }

        $this->applyParsedFields($sale, $result['parsed'] ?? []);
        $sale->einvoice_status = 'generated';
        $sale->einvoice_error = null;
        $sale->einvoice_synced_at = now();
        $sale->save();

        session(['record' => [
            'type' => 'success',
            'status' => $result['message'] ?? 'IRN generated successfully.',
        ]]);

        return back();
    }

    public function cancelIrn(Request $request, int $id): RedirectResponse
    {
        $sale = Sale::findOrFail($id);
        if (empty($sale->irn)) {
            $this->markFailure($sale, 'IRN not found for this invoice.');

            return back();
        }

        $reason = (string) $request->input('reason_code', '1');
        $remark = (string) $request->input('remark', 'Cancelled from CMS');
        $result = $this->eInvoiceApiService->cancelIrn($sale->irn, $reason, $remark);

        if (! $result['ok']) {
            $this->markFailure($sale, $result['message'] ?? 'IRN cancellation failed.');

            return back();
        }

        $sale->einvoice_status = 'cancelled';
        $sale->einvoice_error = null;
        $sale->einvoice_synced_at = now();
        $sale->save();

        session(['record' => [
            'type' => 'success',
            'status' => $result['message'] ?? 'IRN cancelled successfully.',
        ]]);

        return back();
    }

    public function generateEwb(Request $request, int $id): RedirectResponse
    {
        $sale = Sale::findOrFail($id);
        if (empty($sale->irn)) {
            $this->markFailure($sale, 'Generate IRN first, then generate eWay bill.');

            return back();
        }

        $transportPayload = array_filter([
            'Distance' => $request->input('distance'),
            'TransMode' => $request->input('trans_mode'),
            'TransId' => $request->input('trans_id'),
            'TransName' => $request->input('trans_name'),
            'VehNo' => $request->input('vehicle_no'),
            'VehType' => $request->input('vehicle_type'),
        ], fn ($value) => ! is_null($value) && $value !== '');

        $result = $this->eInvoiceApiService->generateEwbByIrn($sale->irn, $transportPayload);

        if (! $result['ok']) {
            $this->markFailure($sale, $result['message'] ?? 'eWay bill generation failed.');

            return back();
        }

        $this->applyParsedFields($sale, $result['parsed'] ?? []);
        $sale->einvoice_status = 'ewb_generated';
        $sale->einvoice_error = null;
        $sale->einvoice_synced_at = now();
        $sale->save();

        session(['record' => [
            'type' => 'success',
            'status' => $result['message'] ?? 'eWay bill generated successfully.',
        ]]);

        return back();
    }

    public function cancelEwb(Request $request, int $id): RedirectResponse
    {
        $sale = Sale::findOrFail($id);
        if (empty($sale->ewb_no)) {
            $this->markFailure($sale, 'eWay bill number not found for this invoice.');

            return back();
        }

        $reasonCode = (string) $request->input('reason_code', '1');
        $reasonRemark = (string) $request->input('remark', 'Cancelled from CMS');
        $result = $this->eInvoiceApiService->cancelEwb((string) $sale->ewb_no, $reasonCode, $reasonRemark);

        if (! $result['ok']) {
            $this->markFailure($sale, $result['message'] ?? 'eWay bill cancellation failed.');

            return back();
        }

        $sale->einvoice_status = 'ewb_cancelled';
        $sale->einvoice_error = null;
        $sale->einvoice_synced_at = now();
        $sale->save();

        session(['record' => [
            'type' => 'success',
            'status' => $result['message'] ?? 'eWay bill cancelled successfully.',
        ]]);

        return back();
    }

    public function syncIrn(int $id): RedirectResponse
    {
        $sale = Sale::findOrFail($id);
        if (empty($sale->irn)) {
            $this->markFailure($sale, 'IRN not found for sync.');

            return back();
        }

        $result = $this->eInvoiceApiService->getByIrn($sale->irn);
        if (! $result['ok']) {
            $this->markFailure($sale, $result['message'] ?? 'IRN sync failed.');

            return back();
        }

        $this->applyParsedFields($sale, $result['parsed'] ?? []);
        $sale->einvoice_status = 'synced';
        $sale->einvoice_error = null;
        $sale->einvoice_synced_at = now();
        $sale->save();

        session(['record' => [
            'type' => 'success',
            'status' => $result['message'] ?? 'IRN synced successfully.',
        ]]);

        return back();
    }

    private function applyParsedFields(Sale $sale, array $parsed): void
    {
        $sale->fill([
            'irn' => $parsed['irn'] ?? $sale->irn,
            'irn_ack_no' => $parsed['irn_ack_no'] ?? $sale->irn_ack_no,
            'irn_ack_date' => $parsed['irn_ack_date'] ?? $sale->irn_ack_date,
            'irn_signed_qr_code' => $parsed['irn_signed_qr_code'] ?? $sale->irn_signed_qr_code,
            'ewb_no' => $parsed['ewb_no'] ?? $sale->ewb_no,
            'ewb_date' => $parsed['ewb_date'] ?? $sale->ewb_date,
            'ewb_valid_till' => $parsed['ewb_valid_till'] ?? $sale->ewb_valid_till,
        ]);
    }

    private function markFailure(Sale $sale, string $message): void
    {
        $sale->einvoice_status = 'failed';
        $sale->einvoice_error = $message;
        $sale->einvoice_synced_at = now();
        $sale->save();

        session(['record' => [
            'type' => 'danger',
            'status' => $message,
        ]]);
    }
}

