<?php

namespace Modules\Partnership\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Partnership\Http\Models\Partner;
use Modules\Partnership\Http\Requests\PartnerRequest;
use Modules\Partnership\Services\PartnerService;
use Modules\Partnership\Services\PartnerTransactionService;
use Yajra\DataTables\Facades\DataTables;

class PartnerController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    public $partnerTransactionService;

    public $partnerService;

    public function __construct(PartnerTransactionService $partnerTransactionService, PartnerService $partnerService)
    {
        $this->partnerTransactionService = $partnerTransactionService;

        $this->partnerService = $partnerService;
    }

    public function getLang(): array
    {

        return [
            'partner_list' => __('partnership::partner.partner_list'),
            'partner_create' => __('partnership::partner.create_partner'),
            'partner_edit' => __('partnership::partner.edit_partner'),
            'partner_details' => __('partnership::partner.partner_details'),
            'partner_update' => __('partnership::partner.partner_update'),
            'partner_type' => 'partner',

        ];
    }

    /**
     * Create a new partner.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $lang = $this->getLang();

        return view('partnership::partner.create', compact('lang'));
    }

    /**
     * Edit a partner.
     *
     * @param  int  $id  The ID of the partner to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id): View
    {
        $lang = $this->getLang();

        $partner = Partner::whereId($id)->get()->first();
        if (! $partner) {
            return abort(403, 'Unauthorized');
        }

        $transaction = $partner->transaction()->get()->first(); // Used Morph

        $opening_balance_type = 'to_pay';
        $to_receive = false;
        if ($transaction) {
            $transaction->opening_balance = ($transaction->to_pay > 0) ? $this->formatWithPrecision($transaction->to_pay, comma: false) : $this->formatWithPrecision($transaction->to_receive, comma: false);

            $opening_balance_type = ($transaction->to_pay > 0) ? 'to_pay' : 'to_receive';
        }

        /**
         * Todays Date
         * */
        $todaysDate = $this->toUserDateFormat(now());

        return view('partnership::partner.edit', compact('partner', 'transaction', 'opening_balance_type', 'todaysDate', 'lang'));
    }

    /**
     * Return JsonResponse
     * */
    public function store(PartnerRequest $request)
    {
        try {

            DB::beginTransaction();

            /**
             * Get the validated data from the ItemRequest
             * */
            $validatedData = $request->validated();

            /**
             * Know which partner type
             * `supplier` or `customer`
             * */
            $partnerType = $request->partner_type;

            /**
             * Know which operation want
             * `save` or `update`
             * */
            $operation = $request->operation;

            /**
             * Save or Update the Items Model
             * */
            $recordsToSave = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'phone' => $request->phone,
                'whatsapp' => $request->whatsapp,
                'tax_number' => $request->tax_number,
                'address' => $request->address,
                'status' => $validatedData['status'],
                // 'default_partner'   =>  $validatedData['default_partner'],
                'currency_id' => $request->currency_id,
            ];
            if ($request->has('state_id')) {
                $recordsToSave['state_id'] = $request->state_id ?? null;
            }

            if ($operation == 'save') {
                $partnerModel = Partner::create($recordsToSave);
            } else {
                $partnerModel = Partner::find($request->partner_id);

                // Load Partner Transactions
                $partnerTransactions = $partnerModel->transaction;

                foreach ($partnerTransactions as $partnerTransaction) {
                    // Delete Account Transaction
                    // $partnerTransaction->accountTransaction()->delete();

                    // Delete Partner Transaction
                    $partnerTransaction->delete();
                }

                // Update the partner records
                $partnerModel->update($recordsToSave);
            }

            $request->request->add(['partnerModel' => $partnerModel]);

            /**
             * Update Partner Transaction for opening Balance
             * */
            $transaction = $this->partnerTransactionService->recordPartnerTransactionEntry($partnerModel, [
                'transaction_date' => $request->transaction_date,
                'to_pay' => ($request->opening_balance_type == 'to_pay') ? $request->opening_balance ?? 0 : 0,
                'to_receive' => ($request->opening_balance_type == 'to_receive') ? $request->opening_balance ?? 0 : 0,
            ]);

            if (! $transaction) {
                throw new \Exception(__('partner.failed_to_record_partner_transactions'));
            }

            // Update Other Default Partner as a 0
            // if($request->default_partner){
            //     Partner::where('partner_type', $partnerType)
            //          ->whereNot('id', $request->partnerModel->id)
            //          ->update(['default_partner' => 0]);
            // }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
                'data' => [
                    'id' => $partnerModel->id,
                    'first_name' => $partnerModel->first_name,
                    'last_name' => $partnerModel->last_name ?? '',
                    'curreny_id' => $partnerModel->currency_id,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 409);

        }
    }

    /**
     * partnerType: customer or supplier
     * */
    public function list(): View
    {
        $lang = $this->getLang();

        return view('partnership::partner.list', compact('lang'));
    }

    public function datatableList(Request $request)
    {
        $data = Partner::query();

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search')) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhere('whatsapp', 'like', "%{$searchTerm}%")
                            ->orWhere('phone', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('name', function ($row) {
                return $row->first_name.' '.$row->last_name;
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('balance', function ($row) {
                // Store the balance data in the row
                $row->balanceData = $this->partnerService->getPartnerBalance([$row->id]);

                // Return the formatted balance
                return $this->formatWithPrecision($row->balanceData['balance']);
            })
            ->addColumn('balance_type', function ($row) {
                // Return the status using the stored balance data
                return $row->balanceData['status'];
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;

                $editUrl = route('partnership.partner.edit', ['id' => $id]);

                $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">

                                <li>
                                    <a class="dropdown-item" href="'.$editUrl.'"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>';
                $actionBtn .= '<li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
                                </li>
                            </ul>
                        </div>';

                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function delete(Request $request): JsonResponse
    {

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Partner::find($recordId);
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
            // Delete partner
            Partner::whereIn('id', $selectedRecordIds)
                ->whereDefaultPartner(0) // Do not delete the default partner
                ->delete();

        } catch (QueryException $e) {
            return response()->json(['message' => __('app.cannot_delete_records')], 409);
        }

        return response()->json([
            'status' => true,
            'message' => __('app.record_deleted_successfully'),
        ]);
    }

    /**
     * Ajax Response
     * Search Bar for select2 list
     * */
    public function getAjaxSearchBarList(Request $request)
    {
        $search = $request->input('search', '');
        $page = (int) $request->input('page', 1);
        $perPage = 8;
        $offset = ($page - 1) * $perPage;

        $query = Partner::with('currency')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });

        $total = $query->count();

        $partners = $query
            ->select('id', 'first_name', 'last_name', 'mobile')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $results = $partners->map(function ($partner) {
            return [
                'id' => $partner->id,
                'text' => trim($partner->first_name.' '.$partner->last_name),
                'mobile' => $partner->mobile ?? '',
            ];
        })->toArray();

        $hasMore = ($offset + $perPage) < $total;

        return response()->json([
            'results' => $results,
            'hasMore' => $hasMore,
        ]);
    }
}
