<?php

namespace App\Http\Controllers;

use App\Models\Expenses\Expense;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\Party\Party;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleOrder;
use App\Services\PartyService;
use App\Traits\FormatNumber;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use formatNumber;

    public function __construct(public PartyService $partyService)
    {
        //
    }

    public function index()
    {

        $pendingSaleOrders = SaleOrder::whereDoesntHave('sale')
            ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->count();
        $totalCompletedSaleOrders = SaleOrder::whereHas('sale')
            ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->count();

        $partyBalance = $this->paymentReceivables();
        $totalPaymentReceivables = $this->formatWithPrecision($partyBalance['receivable']);
        $totalPaymentPaybles = $this->formatWithPrecision($partyBalance['payable']);

        $pendingPurchaseOrders = PurchaseOrder::whereDoesntHave('purchase')
            ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->count();
        $totalCompletedPurchaseOrders = PurchaseOrder::whereHas('purchase')
            ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->count();

        $totalCustomers = Party::where('party_type', 'customer')
            ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('created_by', auth()->user()->id);
            })
            ->count();

        $totalExpense = Expense::when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
            return $query->where('created_by', auth()->user()->id);
        })
            ->sum('grand_total');
        $totalExpense = $this->formatWithPrecision($totalExpense);

        $recentInvoices = Sale::when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
            return $query->where('created_by', auth()->user()->id);
        })
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $saleVsPurchase = $this->saleVsPurchase();
        $trendingItems = $this->trendingItems();
        $lowStockItems = $this->getLowStockItemRecords();

        return view('dashboard', compact(
            'pendingSaleOrders',
            'pendingPurchaseOrders',

            'totalCompletedSaleOrders',
            'totalCompletedPurchaseOrders',

            'totalCustomers',
            'totalPaymentReceivables',
            'totalPaymentPaybles',
            'totalExpense',

            'saleVsPurchase',
            'trendingItems',
            'lowStockItems',
            'recentInvoices',
        ));
    }

    public function saleVsPurchase()
    {
        $labels = [];
        $sales = [];
        $purchases = [];

        $now = now();
        for ($i = 0; $i < 6; $i++) {
            $month = $now->copy()->subMonths($i)->format('M Y');
            $labels[] = $month;

            // Get value for this month, e.g. from database
            $sales[] = Sale::whereMonth('sale_date', $now->copy()->subMonths($i)->month)
                ->whereYear('sale_date', $now->copy()->subMonths($i)->year)
                ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                    return $query->where('created_by', auth()->user()->id);
                })
                ->count();

            $purchases[] = Purchase::whereMonth('purchase_date', $now->copy()->subMonths($i)->month)
                ->whereYear('purchase_date', $now->copy()->subMonths($i)->year)
                ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                    return $query->where('created_by', auth()->user()->id);
                })
                ->count();

        }

        $labels = array_reverse($labels);
        $sales = array_reverse($sales);
        $purchases = array_reverse($purchases);

        $saleVsPurchase = [];

        for ($i = 0; $i < count($labels); $i++) {
            $saleVsPurchase[] = [
                'label' => $labels[$i],
                'sales' => $sales[$i],
                'purchases' => $purchases[$i],
            ];
        }

        return $saleVsPurchase;
    }

    public function trendingItems(): array
    {
        // Get top 4 trending items (adjust limit as needed)
        return ItemTransaction::query()
            ->select([
                'items.name',
                DB::raw('SUM(item_transactions.quantity) as total_quantity'),
            ])
            ->join('items', 'items.id', '=', 'item_transactions.item_id')
            ->where('item_transactions.transaction_type', getMorphedModelName(Sale::class))
            ->when(auth()->user()->can('dashboard.can.view.self.dashboard.details.only'), function ($query) {
                return $query->where('item_transactions.created_by', auth()->user()->id);
            })
            ->groupBy('item_transactions.item_id', 'items.name')
            ->orderByDesc('total_quantity')
            ->limit(4)
            ->get()
            ->toArray();
    }

    public function paymentReceivables()
    {
        $customerIds = Party::where('party_type', 'customer')->pluck('id');
        $supplierIds = Party::where('party_type', 'supplier')->pluck('id');

        $customerIds = $customerIds->toArray();
        $supplierIds = $supplierIds->toArray();

        $customerBalance = $this->partyService->getPartyBalance($customerIds);
        $supplierBalance = $this->partyService->getPartyBalance($supplierIds);

        return [
            'payable' => abs($supplierBalance['balance']),
            'receivable' => abs($customerBalance['balance']),
        ];

    }

    public function getLowStockItemRecords()
    {
        return Item::with('baseUnit')
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0)
            ->orderByDesc('current_stock')
            ->limit(10)->get();
    }
}
