<?php

namespace Modules\Partnership\Http\Controllers;

use App\Enums\App;
use App\Enums\General;
use App\Http\Controllers\Controller;
use App\Models\Contract\ContractOrder;
use App\Models\Contract\Quotation;
use App\Models\Items\Item;
use App\Models\Prefix;
use App\Services\AccountTransactionService;
use App\Services\CacheService;
use App\Services\Communication\Email\ContractEmailNotificationService;
use App\Services\Communication\Sms\ContractSmsNotificationService;
use App\Services\GeneralDataService;
use App\Services\ItemService;
use App\Services\ItemTransactionService;
use App\Services\PaymentTransactionService;
use App\Services\PaymentTypeService;
use App\Traits\FormatNumber;
use App\Traits\FormatsDateInputs;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Partnership\Http\Models\Contract;
use Modules\Partnership\Http\Models\ContractItem;
use Modules\Partnership\Http\Models\Partner;
use Modules\Partnership\Http\Requests\ContractRequest;
use Modules\Partnership\Services\ContractItemsService;
use Modules\Partnership\Services\PartnerService;
use Mpdf\Mpdf;
use Yajra\DataTables\Facades\DataTables;

class ContractController extends Controller
{
    use FormatNumber;
    use FormatsDateInputs;

    protected $companyId;

    private $itemTransactionService;

    private $itemService;

    private $partnerService;

    public $previousHistoryOfItems;

    public $contractEmailNotificationService;

    public $contractSmsNotificationService;

    private $contractItemsService;

    public function __construct(
        ItemTransactionService $itemTransactionService,
        ItemService $itemService,
        PartnerService $partnerService,
        ContractItemsService $contractItemsService,
    ) {
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
        $this->itemTransactionService = $itemTransactionService;
        $this->itemService = $itemService;
        $this->partnerService = $partnerService;
        $this->contractItemsService = $contractItemsService;
        $this->previousHistoryOfItems = [];
    }

    /**
     * Create a new order.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        $prefix = Contract::latest()->first();
        $lastCountId = $this->getLastCountId();
        $data = [
            'prefix_code' => $prefix->prefix_code ?? '',
            'count_id' => ($lastCountId + 1),
        ];

        return view('partnership::contract.create', compact('data'));
    }

    /**
     * Get last count ID
     * */
    public function getLastCountId()
    {
        return Contract::select('count_id')->orderBy('id', 'desc')->first()?->count_id ?? 0;
    }

    /**
     * List the orders
     *
     * @return \Illuminate\View\View
     */
    public function list(): View
    {
        return view('partnership::contract.list');
    }

    /**
     * Edit a Contract Order.
     *
     * @param  int  $id  The ID of the expense to edit.
     * @return \Illuminate\View\View
     */
    public function edit($id): View
    {
        $contract = Contract::findOrFail($id);

        $contract->operation = 'update';

        // Item Details
        // Prepare item transactions with associated units
        $allUnits = CacheService::get('unit');

        $allPartners = Partner::select('id', 'first_name', 'last_name')->get();

        // get contract items share holdes holders
        $shareCounts = ContractItem::getActiveShareholderCounts($contract->contractItems->pluck('item_id')->toArray());

        $contractItems = $contract->contractItems->map(function ($contractItem) use ($allPartners, $shareCounts) {
            $itemData = $contractItem->toArray();

            // Add partnerList to the item data
            $itemData['partnerList'] = $allPartners;
            $itemData['item_name'] = $contractItem->item->name;
            $itemData['brand_name'] = $contractItem->item->brand->name ?? '';
            $itemData['totalShareHolders'] = $shareCounts[$contractItem->item_id] ?? 0;

            return $itemData;
        })->toArray();

        $contractItemsJson = json_encode($contractItems);

        return view('partnership::contract.edit', compact('contract', 'contractItemsJson'));
    }

    /**
     * View Contract Order details
     *
     * @param  int  $id,  the ID of the order
     * @return \Illuminate\View\View
     */
    public function details($id): View
    {
        $contract = Contract::findOrFail($id);

        return view('partnership::contract.details', compact('contract'));
    }

    /**
     * Store Records
     * */
    public function store(ContractRequest $request): JsonResponse
    {
        try {

            DB::beginTransaction();
            // Get the validated data from the expenseRequest
            $validatedData = $request->validated();

            if ($request->operation == 'save') {
                // Create a new contract record using Eloquent and save it
                $newContract = Contract::create($validatedData);

                $request->request->add(['contract_id' => $newContract->id]);
            } else {
                $fillableColumns = [
                    'contract_date' => $validatedData['contract_date'],
                    'reference_no' => $validatedData['reference_no'],
                    'prefix_code' => $validatedData['prefix_code'],
                    'count_id' => $validatedData['count_id'],
                    'contract_code' => $validatedData['contract_code'],
                    'remarks' => $validatedData['remarks'],
                ];

                $newContract = Contract::findOrFail($validatedData['contract_id']);
                $newContract->update($fillableColumns);
                $newContract->contractItems()->delete();
            }

            $request->request->add(['modelName' => $newContract]);

            /**
             * Save Table Items in Contract Items Table
             * */
            $ContractItemsArray = $this->saveContractItems($request);
            if (! $ContractItemsArray['status']) {
                throw new \Exception($ContractItemsArray['message']);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_saved_successfully'),
                'id' => $request->contract_id,

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
     * Save Contract Items
     * */
    public function saveContractItems($request)
    {
        $itemsCount = $request->row_count;

        // $isWholecontractCustomer = $request->only('is_wholecontract_customer')['is_wholecontract_customer'];

        for ($i = 0; $i < $itemsCount; $i++) {
            /**
             * If array record not exist then continue forloop
             * */
            if (! isset($request->item_id[$i])) {
                continue;
            }

            /**
             * Data index start from 0
             * */
            $itemDetails = Item::find($request->item_id[$i]);
            $itemName = $itemDetails->name;
            $shareValue = $request->share_value[$i];
            $partnerId = $request->partner_id[$i];

            // Validate Share Value should be a number and not negative
            if (! is_numeric($shareValue) || $shareValue < 0) {
                throw new \Exception(__('partnership::contract.invalid_share_value', ['item' => $itemName]));
            }

            // Validate Partner ID, Should not be empty
            if (empty($partnerId)) {
                throw new \Exception(__('partnership::partner.select_partner_for_item', ['item' => $itemName]));
            }

            /**
             * Item Transaction Entry
             * */
            $transaction = $this->contractItemsService->recordPartnerContractItems($request->modelName, [
                'contract_date' => $request->contract_date,
                'item_id' => $request->item_id[$i],
                'description' => $request->description[$i],
                'share_type' => $request->share_type[$i],
                'share_value' => $shareValue,
                'partner_id' => $partnerId,
            ]);

            // return $transaction;
            if (! $transaction) {
                throw new \Exception('Failed to record Item Transaction Entry!');
            }

        }// for end

        return ['status' => true];
    }

    /**
     * Datatabale
     * */
    public function datatableList(Request $request)
    {

        $data = Contract::when($request->user_id, function ($query) use ($request) {
            return $query->where('created_by', $request->user_id);
        })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->where('contract_date', '>=', $this->toSystemDateFormat($request->from_date));
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->where('contract_date', '<=', $this->toSystemDateFormat($request->to_date));
            })
            ->when(! auth()->user()->can('partnership::contract.can.view.other.users.partnership::contracts'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            });

        return DataTables::of($data)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchTerm = $request->search['value'];
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('contract_code', 'like', "%{$searchTerm}%")
                            ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                                $userQuery->where('username', 'like', "%{$searchTerm}%");
                            });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('created_at', function ($row) {
                return $row->created_at->format(app('company')['date_format']);
            })
            ->addColumn('username', function ($row) {
                return $row->user->username ?? '';
            })
            ->addColumn('action', function ($row) {
                $id = $row->id;

                $editUrl = route('partnership.contract.edit', ['id' => $id]);
                $detailsUrl = route('partnership.contract.details', ['id' => $id]);

                $actionBtn = '<div class="dropdown ms-auto">
                            <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded font-22 text-option"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="'.$editUrl.'"><i class="bi bi-trash"></i><i class="bx bx-edit"></i> '.__('app.edit').'</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="'.$detailsUrl.'"></i><i class="bx bx-show-alt"></i> '.__('app.details').'</a>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item text-danger deleteRequest" data-delete-id='.$id.'><i class="bx bx-trash"></i> '.__('app.delete').'</button>
                                </li>
                            </ul>
                        </div>';

                return $actionBtn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Delete Contract Records
     * */
    public function delete(Request $request): JsonResponse
    {

        DB::beginTransaction();

        $selectedRecordIds = $request->input('record_ids');

        // Perform validation for each selected record ID
        foreach ($selectedRecordIds as $recordId) {
            $record = Contract::find($recordId);
            if (! $record) {
                // Invalid record ID, handle the error (e.g., show a message, log, etc.)
                return response()->json([
                    'status' => false,
                    'message' => __('app.invalid_record_id', ['record_id' => $recordId]),
                ], 404);

            }
            // You can perform additional validation checks here if needed before deletion
        }

        /**
         * All selected record IDs are valid, proceed with the deletion
         * Delete all records with the selected IDs in one query
         * */
        try {
            // Attempt deletion (as in previous responses)
            Contract::whereIn('id', $selectedRecordIds)->chunk(100, function ($contracts) {
                foreach ($contracts as $contract) {
                    // Purchasr Item delete and update the stock
                    foreach ($contract->contractItems as $contractItem) {
                        // delete item Transactions
                        $contractItem->delete();
                    }// contract account

                    // Delete Contract
                    $contract->delete();
                }// contracts

            }); // chunk

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => __('app.record_deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => __('app.cannot_delete_records'),
            ], 409);
        }
    }

    /**
     * Prepare Email Content to view
     * */
    public function getEmailContent($id)
    {
        $model = Contract::with('party')->find($id);

        $emailData = $this->contractEmailNotificationService->contractCreatedEmailNotification($id);

        $subject = ($emailData['status']) ? $emailData['data']['subject'] : '';
        $content = ($emailData['status']) ? $emailData['data']['content'] : '';

        $data = [
            'email' => $model->party->email,
            'subject' => $subject,
            'content' => $content,
        ];

        return $data;
    }

    /**
     * Ajax Response
     * Search Bar for select2 list
     * */
    public function getAjaxSearchBarList()
    {
        $search = request('search');
        $page = request('page', 1);
        $perPage = 10;

        $query = Contract::where(function ($q) use ($search) {
            $q->where('contract_code', 'LIKE', "%{$search}%")
                ->orWhereHas('partner', function ($partnerQuery) use ($search) {
                    $partnerQuery->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                });
        });

        $total = $query->count();
        $invoices = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'text' => $invoice->contract_code,
                'partner_name' => optional($invoice->partner)->getFullName(),
            ];
        });

        return response()->json([
            'results' => $results,
            'hasMore' => ($page * $perPage) < $total,
        ]);
    }

    /**
     * Autocomplete search
     * */
    public function getAjaxItemSearchWithPartners()
    {
        $search = request('search');
        $page = request('page', 1);
        $perPage = 10;

        $query = Item::with('brand')
            ->where('name', 'LIKE', "%{$search}%");

        $total = $query->count();

        $records = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $itemIds = $records->pluck('id');

        // ✅ Use the method from ContractItem model
        $shareCounts = ContractItem::getActiveShareholderCounts($itemIds);

        $partners = Partner::select('id', 'first_name', 'last_name')->get();

        $results = $records->map(function ($record) use ($partners, $shareCounts) {
            return [
                'id' => $record->id,
                'text' => $record->name,
                'brand_name' => $record->brand->name ?? '',
                'shareValue' => 0,
                'partnerList' => $partners,
                'totalShareHolders' => $shareCounts[$record->id] ?? 0,
            ];
        });

        return response()->json([
            'results' => $results,
            'hasMore' => ($page * $perPage) < $total,
        ]);
    }

    public function showShareHoldersModal(int $itemId)
    {
        $item = Item::findOrFail($itemId);

        $avgPrices = $this->itemTransactionService->calculateEachItemSaleAndPurchasePrice([$itemId], warehouseId: null, useGlobalPurchasePrice: true);
        $item->avg_purchase_price = $avgPrices[$itemId]['purchase']['average_purchase_price'] ?? 0;
        $item->avg_sale_price = $avgPrices[$itemId]['sale']['average_sale_price'] ?? 0;

        $contractItems = ContractItem::with('partner')
            ->where('item_id', $itemId)
            ->active()// scope
            ->get();

        // Return rendered HTML, not JSON
        $html = view('partnership::modals.share-holders', ['item' => $item, 'contractItems' => $contractItems])->render();

        return response()->json(['html' => $html]);

    }
}
