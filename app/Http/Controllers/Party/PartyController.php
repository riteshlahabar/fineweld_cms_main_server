<?php

namespace App\Http\Controllers\Party;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\PartyRequest;
use App\Models\Accounts\Account;
use App\Models\Party\Party;
use App\Services\AccountTransactionService;
use App\Services\PartyService;
use App\Services\PartyTransactionService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Symfony\Component\HttpFoundation\StreamedResponse;


class PartyController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    public $accountTransactionService;
    public $partyTransactionService;
    public $partyService;

    public function __construct(
        PartyTransactionService $partyTransactionService, 
        AccountTransactionService $accountTransactionService, 
        PartyService $partyService
    ) {
        $this->partyTransactionService = $partyTransactionService;
        $this->accountTransactionService = $accountTransactionService;
        $this->partyService = $partyService;
    }

public function publicForm(): View
{
    $lang = [
        'party_list'    => 'Vendor Registration',
        'party_create'  => 'Vendor Registration',
        'party_update'  => 'Vendor Registration',
        'party_type'    => 'vendor',
        'party_details' => 'Vendor Details',
    ];

    return view('party.form', compact('lang'));
}

    /**
     * Vendor Language Array - Routes remain 'party/{partyType}'
     */
    public function getLang($partyType): array
{
    // Force vendor regardless of URL parameter
    return [
        'party_list' => 'Vendors List',
        'party_create' => 'Create Vendor', 
        'party_update' => 'Update Vendor',
        'party_type' => 'vendor',
        'party_details' => 'Vendor Details',
    ];
}


    public function create($partyType): View
    {
        $lang = $this->getLang($partyType);
        return view('party.create', compact('lang'));
    }

public function export($partyType, Request $request)
{
    $data = Party::where('party_type', $partyType)->limit(5000)->get()->toArray();
    
    if (empty($data)) {
    abort(404, 'No data found');
}

    $type = $request->type; // excel | csv | pdf

    // CSV & Excel (same output)
    if ($type === 'csv' || $type === 'excel') {

        return new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($data[0])); // headers

            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="parties.' . $type . '"',
        ]);
    }

    // PDF (very basic)
    if ($type === 'pdf') {
        $html = '<table border="1" cellpadding="5"><tr>';
        foreach (array_keys($data[0]) as $col) {
            $html .= "<th>$col</th>";
        }
        $html .= '</tr>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $val) {
                $html .= "<td>$val</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return \PDF::loadHTML($html)->download('parties.pdf');
    }

    abort(404);
}

    public function edit($partyType, $id): View
    {
        $lang = $this->getLang($partyType);
        $party = Party::where('party_type', 'vendor')->whereId($id)->first();
        
        if (!$party) {
            return abort(403, 'Vendor not found');
        }

        $transaction = $party->transaction()->first();
        $opening_balance_type = 'to_pay';
        
        if ($transaction) {
            $transaction->opening_balance = ($transaction->to_pay > 0) 
                ? $this->formatWithPrecision($transaction->to_pay, comma: false) 
                : $this->formatWithPrecision($transaction->to_receive, comma: false);
            $opening_balance_type = ($transaction->to_pay > 0) ? 'to_pay' : 'to_receive';
        }

        $todaysDate = $this->toUserDateFormat(now());
        return view('party.edit', compact('party', 'transaction', 'opening_balance_type', 'todaysDate', 'lang'));
    }

  public function store(PartyRequest $request): JsonResponse
{
    try {
        DB::beginTransaction();

        // 1. Prepare the data array
        $recordsToSave = [
            // ✅ COMPANY FIELDS
            'company_name' => $request->company_name,
            'company_type' => $request->company_type,
            'vendor_type' => $request->vendor_type,
            'company_pan' => $request->company_pan,
            'company_gst' => $request->company_gst,
            'company_tan' => $request->company_tan,
            'company_msme' => $request->company_msme,
            'date_of_incorporation' => $request->date_of_incorporation,

            // ✅ PRIMARY CONTACT
            'primary_name' => $request->primary_name,
            'primary_email' => $request->primary_email,
            'primary_mobile' => $request->primary_mobile,
            'primary_whatsapp' => $request->primary_whatsapp,
            'primary_dob' => $request->primary_dob,

            // ✅ SECONDARY CONTACT
            'secondary_name' => $request->secondary_name,
            'secondary_email' => $request->secondary_email,
            'secondary_mobile' => $request->secondary_mobile,
            'secondary_whatsapp' => $request->secondary_whatsapp,
            'secondary_dob' => $request->secondary_dob,

            // ✅ BANK DETAILS
            'bank_name' => $request->bank_name,
            'bank_branch' => $request->bank_branch,
            'bank_account_no' => $request->bank_account_no,
            'ifsc_code' => $request->ifsc_code,
            'micr_code' => $request->micr_code,

            // ✅ ADDRESS
            'billing_address' => $request->billing_address,
            'shipping_address' => $request->shipping_address,

            // ✅ SETTINGS
            'default_party' => $request->boolean('default_party') ? 1 : 0,
            
            // Note: We handle status, currency, and party_type conditionally below
            // to ensure we don't accidentally reset them on update if they aren't in the form
        ];

        // 2. Handle File Uploads
        // (This logic is fine: if no new file is uploaded, the key is not added, 
        // so existing file paths in DB won't be overwritten during update)
        $uploadPath = public_path('uploads/party_documents');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $documentFields = [
            'pan_document', 'tan_document', 'gst_document', 
            'msme_document', 'cancelled_cheque',
        ];

        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $filename);
                $recordsToSave[$field] = 'uploads/party_documents/' . $filename;
            }
        }

        // 3. CHECK IF UPDATE OR CREATE
        // We check if party_id is present in the request
        $partyId = $request->input('party_id');
        $partyModel = null;

        if ($partyId) {
            // === UPDATE LOGIC ===
            $partyModel = Party::findOrFail($partyId);
            
            // Only update party_type/currency if specifically passed, otherwise keep existing
            // (Usually these don't change on edit)
            if($request->has('currency_id')) $recordsToSave['currency_id'] = 1;
            
            $partyModel->update($recordsToSave);
            $message = 'Vendor updated successfully!';
        } else {
            // === CREATE LOGIC ===
            // Add default fields that are needed only for new records
            $recordsToSave['party_type'] = 'vendor';
            $recordsToSave['status'] = 1; // Default Active
            $recordsToSave['currency_id'] = 1;

            $partyModel = Party::create($recordsToSave);
            $message = 'Vendor created successfully!';
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $partyModel->only(['id', 'company_name', 'primary_name'])
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
 /**
     * For public store method
     */

 public function publicStore(Request $request): JsonResponse
{
    try {
        // VALIDATION for PUBLIC FORM
        $request->validate([
            // Company Details
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|in:proprietor,partnership,private_limited,public_limited,one_person_company,limited_liability_partnership',
            'vendor_type' => 'required|in:customer,supplier,both',
            'company_pan' => 'required|string|max:10',
            'company_gst' => 'required|string|max:15',
            'company_tan' => 'required|string|max:10',
            'company_msme' => 'required|string|max:50',
            'date_of_incorporation' => 'required|date',
            
            // Primary Contact
            'primary_name' => 'required|string|max:255',
            'primary_email' => 'required|email|max:255',
            'primary_mobile' => 'required|digits:10',
            'primary_whatsapp' => 'required|digits:10',
            'primary_dob' => 'required|date',
            
            // Secondary Contact
            'secondary_name' => 'required|string|max:255',
            'secondary_email' => 'required|email|max:255',
            'secondary_mobile' => 'required|digits:10',
            'secondary_whatsapp' => 'required|digits:10',
            'secondary_dob' => 'required|date',
            
            // Address
            'billing_address' => 'required|string',
            'shipping_address' => 'required|string',
            
            // Bank Details
            'bank_name' => 'required|string|max:255',
            'bank_branch' => 'required|string|max:255',
            'bank_account_no' => 'required|string|max:20',
            'ifsc_code' => 'required|string|max:20',
'micr_code' => 'required|string|max:20',
            
            // Documents
            'pan_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'tan_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'gst_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'msme_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'cancelled_cheque' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        DB::beginTransaction();
        
        $recordsToSave = [
            // ✅ ALL PUBLIC FORM FIELDS
            'company_name' => $request->company_name,
            'company_type' => $request->company_type,
            'vendor_type' => $request->vendor_type,
            'company_pan' => $request->company_pan,
            'company_gst' => $request->company_gst,
            'company_tan' => $request->company_tan,
            'company_msme' => $request->company_msme,
            'date_of_incorporation' => $request->date_of_incorporation,
            
            // Primary Contact
            'primary_name' => $request->primary_name,
            'primary_email' => $request->primary_email,
            'primary_mobile' => $request->primary_mobile,
            'primary_whatsapp' => $request->primary_whatsapp,
            'primary_dob' => $request->primary_dob,
            
            // Secondary Contact
            'secondary_name' => $request->secondary_name,
            'secondary_email' => $request->secondary_email,
            'secondary_mobile' => $request->secondary_mobile,
            'secondary_whatsapp' => $request->secondary_whatsapp,
            'secondary_dob' => $request->secondary_dob,
            
            // Address & Bank
            'billing_address' => $request->billing_address,
            'shipping_address' => $request->shipping_address,
            'bank_name' => $request->bank_name,
            'bank_branch' => $request->bank_branch,
            'bank_account_no' => $request->bank_account_no,
            'ifsc_code' => $request->ifsc_code,
            'micr_code' => $request->micr_code,
            
            // ✅ PUBLIC FORM SPECIFIC
            'party_type' => 'vendor',
            'status' => 1,
            'default_party' => $request->boolean('default_party') ? 1 : 0,
            'currency_id' => 1,
        ];

        // HANDLE DOCUMENT UPLOADS
        $uploadPath = public_path('uploads/party_documents');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $documentFields = [
            'pan_document', 'tan_document', 'gst_document', 
            'msme_document', 'cancelled_cheque'
        ];

        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $filename);
                $recordsToSave[$field] = 'uploads/party_documents/' . $filename;
            }
        }

        $party = Party::create($recordsToSave);
        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Vendor registration submitted successfully! Our team will review and activate your account soon.',
            'data' => $party->only(['id', 'company_name'])
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        DB::rollback();
        \Log::error('Public Vendor Registration Error: ' . $e->getMessage());
        return response()->json([
            'status' => false,
            'message' => 'Registration failed. Please try again or contact support.',
        ], 500);
    }
}



    /**
     * Vendor List - Routes unchanged: party/{partyType}/list
     */
    public function list($partyType): View
    {
        $lang = $this->getLang($partyType);
        return view('party.list', compact('lang'));
    }

public function datatableList(Request $request, $partyType)
{
    try {

        $query = Party::where('party_type', 'vendor');

        if ($request->vendor_type) {
            $query->where('vendor_type', $request->vendor_type);
        }

        if ($request->company_type) {
            $query->where('company_type', $request->company_type);
        }

        return DataTables::of($query)

            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox"
                    class="form-check-input row-select"
                    name="record_ids[]"
                    value="'.$row->id.'">';
            })

            ->addColumn('company_name', fn ($row) =>
                $row->company_name ?? 'N/A'
            )

            ->addColumn('company_address', function ($row) {
                return $row->billing_address
                    ?? $row->shipping_address
                    ?? 'N/A';
            })

            ->editColumn('vendor_type', fn ($row) =>
                ucfirst($row->vendor_type ?? 'N/A')
            )

            ->addColumn('primary_name', fn ($row) =>
                $row->primary_name ?? ''
            )

            ->addColumn('primary_email', fn ($row) =>
                $row->primary_email ?? ''
            )

            ->addColumn('primary_mobile', fn ($row) =>
                $row->primary_mobile ?? ''
            )

            ->addColumn('primary_whatsapp', fn ($row) =>
                $row->primary_whatsapp ?? ''
            )

            ->addColumn('balance', fn () => '₹0.00')

            ->editColumn('status', function ($row) {
                return ((int)$row->status === 1)
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>';
            })

            ->addColumn('action', function ($row) use ($partyType) {
                return '
                <div class="dropdown text-center">
                    <a href="javascript:void(0)" data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded fs-4"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">

                        <li>
                            <a class="dropdown-item"
                               href="'.route('party.edit', [
                                   'partyType' => $partyType,
                                   'id' => $row->id
                               ]).'">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="'.route('party.payment.create', [
                                   'partyType' => $partyType,
                                   'id' => $row->id
                               ]).'">
                                <i class="bx bx-rupee"></i> Add Payment
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item party-payment-history"
                               href="javascript:void(0)"
                               data-party-id="'.$row->id.'">
                                <i class="bx bx-history"></i> Payment History
                            </a>
                        </li>

                        <li>
                            <button class="dropdown-item text-danger deleteRequest"
                                data-delete-id="'.$row->id.'">
                                <i class="bx bx-trash"></i> Delete
                            </button>
                        </li>

                    </ul>
                </div>';
            })

            ->rawColumns(['checkbox', 'status', 'action'])
            ->make(true);

    } catch (\Throwable $e) {

        // IMPORTANT: Return JSON, not HTML
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage(),
        ], 200);
    }
}




    public function delete(Request $request): JsonResponse
    {

        $selectedRecordIds = $request->input('record_ids')?? [];

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Party::find($recordId);
            if (! $record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status' => false,
                    'message' => __('app.invalid_record_id', ['record_id' => $recordId]),
                ]);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */
        try {

            // Attempt deletion (as in previous responses)
            Party::whereIn('id', $selectedRecordIds)->chunk(100, function ($parties) {
                foreach ($parties as $party) {
                    // Load Party Transactions like Opening Balance and other payments
                    $partyTransactions = $party->transaction;

                    foreach ($partyTransactions as $partyTransaction) {
                        // Delete Payment Account Transactions
                        $partyTransaction->accountTransaction()->delete();

                        // Delete Party Transaction
                        $partyTransaction->delete();
                    }
                }
            });

            // Delete party
            Party::whereIn('id', $selectedRecordIds)->delete();

        } catch (QueryException $e) {
            return response()->json(['message' => __('app.cannot_delete_records')], 409);
        }

        return response()->json([
            'status' => true,
            'message' => __('app.record_deleted_successfully'),
        ]);
    }

    public function getAjaxSearchBarList(Request $request)
    {
        $search = $request->input('search');
        $partyType = 'vendor';
        $page = (int) $request->input('page', 1);
        $perPage = 8;
        $offset = ($page - 1) * $perPage;

        $query = Party::with('currency')
    ->where('party_type', $partyType)
    ->where(function ($q) use ($search) {
        $q->where('company_name', 'LIKE', "%{$search}%");
    });

$total = $query->count();

$parties = $query
    ->offset($offset)
    ->limit($perPage)
    ->get();

        $results = $parties->map(function ($party) {
            $partyBalance = $this->partyService->getPartyBalance([$party->id]);
            return [
                'id' => $party->id,
                'text' => $party->company_name ?? $party->contact_person_primary_name,
                'mobile' => $party->contact_person_primary_mobile,
                'billing_address' => $party->billing_address, 
                'shipping_address' => $party->shipping_address,
                'currency_id' => $party->currency_id,
                'to_pay' => $partyBalance['status'] == 'you_pay' ? $partyBalance['balance'] : 0,
                'to_receive' => $partyBalance['status'] == 'you_collect' ? $partyBalance['balance'] : 0,
            ];
        })->toArray();

        return response()->json([
            'results' => $results,
            'hasMore' => ($offset + $perPage) < $total,
        ]);
    }
}
