<?php

namespace App\Http\Controllers;

use App\Models\Expenses\Expense;
use App\Models\Items\Item;
use App\Models\Items\ItemTransaction;
use App\Models\Party\Party;
use App\Models\Purchase\Purchase;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleOrder;
use App\Services\PartyService;
use App\Traits\FormatNumber;
use Illuminate\Database\Eloquent\Builder;
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
        
        $fromDate = request('from_date');
$toDate = request('to_date');

        $pendingSaleOrders = $this->applyDashboardOwnershipFilter(
            Sale::query()
    ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween('sale_date', [$fromDate, $toDate]);
    }),
            'sale.invoice.can.view.other.users.sale.invoices'
        )->count();

        $totalCompletedSaleOrders = $this->applyDashboardOwnershipFilter(
    Sale::query()
        ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('sale_date', [$fromDate, $toDate]);
        }),
    'sale.invoice.can.view.other.users.sale.invoices'
)->sum('grand_total');

        $totalCompletedSaleOrders = $this->formatWithPrecision($totalCompletedSaleOrders);

        $partyBalance = $this->paymentReceivables($fromDate, $toDate);
        $totalPaymentReceivables = $this->formatWithPrecision($partyBalance['receivable']);
        $totalPaymentPaybles = $this->formatWithPrecision($partyBalance['payable']);

        $pendingPurchaseOrders = $this->applyDashboardOwnershipFilter(
            Purchase::query()
    ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween('purchase_date', [$fromDate, $toDate]);
    }),
            'purchase.bill.can.view.other.users.purchase.bills'
        )->count();

        $totalCompletedPurchaseOrders = $this->applyDashboardOwnershipFilter(
    Purchase::query()
        ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('purchase_date', [$fromDate, $toDate]);
        }),
    'purchase.bill.can.view.other.users.purchase.bills'
)->sum('grand_total');

        $totalCompletedPurchaseOrders = $this->formatWithPrecision($totalCompletedPurchaseOrders);

        $totalCustomers = $this->partyQueryByVendorRole('customer')
    ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween('created_at', [
            $fromDate . ' 00:00:00',
            $toDate . ' 23:59:59'
        ]);
    })
    ->count();

        $totalExpense = $this->applyDashboardOwnershipFilter(
            Expense::query()
    ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween('expense_date', [$fromDate, $toDate]);
    }),
            'expense.can.view.other.users.expenses'
        )
            ->sum('grand_total');

        $totalExpense = $this->formatWithPrecision($totalExpense);

        $recentInvoices = $this->applyDashboardOwnershipFilter(
    Sale::query()
        ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('sale_date', [$fromDate, $toDate]);
        }),
    'sale.invoice.can.view.other.users.sale.invoices'
)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $pendingSaleOrderRecords = $this->applyDashboardOwnershipFilter(
    SaleOrder::query()->where('order_status', 'Pending'),
    'sale.order.can.view.other.users.sale.orders'
)
    ->with([
        'party',
        'user',
        'itemTransaction.item',
        'itemTransaction.unit',
    ])
    ->orderByDesc('id')
    ->limit(10)
    ->get();

        $saleVsPurchase = $this->saleVsPurchase($fromDate, $toDate);
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
            'pendingSaleOrderRecords',
        ));
    }

    public function saleVsPurchase($fromDate = null, $toDate = null)
    {
        $labels = [];
        $sales = [];
        $purchases = [];

        $now = now();
        for ($i = 0; $i < 6; $i++) {
            $monthDate = $now->copy()->subMonths($i);
            $month = $monthDate->format('M Y');
            $labels[] = $month;

            // Get value for this month, e.g. from database
           $saleQuery = Sale::query()
    ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween('sale_date', [$fromDate, $toDate]);
    })
                ->where(function ($query) use ($monthDate) {
                    $query->where(function ($subQuery) use ($monthDate) {
                        $subQuery->whereNotNull('sale_date')
                            ->whereMonth('sale_date', $monthDate->month)
                            ->whereYear('sale_date', $monthDate->year);
                    })
                        ->orWhere(function ($subQuery) use ($monthDate) {
                            $subQuery->whereNull('sale_date')
                                ->whereMonth('created_at', $monthDate->month)
                                ->whereYear('created_at', $monthDate->year);
                        });
                });

            $saleQuery = $this->applyDashboardOwnershipFilter(
                $saleQuery,
                'sale.invoice.can.view.other.users.sale.invoices'
            );

            $sales[] = $saleQuery->count();

            $purchaseQuery = Purchase::query()
    ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
        $query->whereBetween('purchase_date', [$fromDate, $toDate]);
    })
                ->where(function ($query) use ($monthDate) {
                    $query->where(function ($subQuery) use ($monthDate) {
                        $subQuery->whereNotNull('purchase_date')
                            ->whereMonth('purchase_date', $monthDate->month)
                            ->whereYear('purchase_date', $monthDate->year);
                    })
                        ->orWhere(function ($subQuery) use ($monthDate) {
                            $subQuery->whereNull('purchase_date')
                                ->whereMonth('created_at', $monthDate->month)
                                ->whereYear('created_at', $monthDate->year);
                        });
                });

            $purchaseQuery = $this->applyDashboardOwnershipFilter(
                $purchaseQuery,
                'purchase.bill.can.view.other.users.purchase.bills'
            );

            $purchases[] = $purchaseQuery->count();

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
        $query = ItemTransaction::query()
        ->when(request('from_date') && request('to_date'), function ($query) {
    $query->whereBetween(
        'item_transactions.created_at',
        [
            request('from_date') . ' 00:00:00',
            request('to_date') . ' 23:59:59'
        ]
    );
})
    ->select([
        'items.name',
        'item_categories.name as category_name',
        DB::raw('SUM(item_transactions.quantity) as total_quantity'),
    ])
    ->join('items', 'items.id', '=', 'item_transactions.item_id')
    ->leftJoin('item_categories', 'item_categories.id', '=', 'items.item_category_id')
    ->where('item_transactions.transaction_type', getMorphedModelName(Sale::class));

        $query = $this->applyDashboardOwnershipFilter(
            $query,
            'sale.invoice.can.view.other.users.sale.invoices',
            'item_transactions.created_by'
        );

        return $query
            ->groupBy(
    'item_transactions.item_id',
    'items.name',
    'item_categories.name'
)
            ->orderByDesc('total_quantity')
            ->limit(4)
            ->get()
            ->toArray();
    }

    public function paymentReceivables($fromDate = null, $toDate = null)
    {
        $customerIds = $this->partyQueryByVendorRole('customer')->pluck('id');

        $supplierIds = $this->partyQueryByVendorRole('supplier')->pluck('id');

        $customerIds = $customerIds->toArray();
        $supplierIds = $supplierIds->toArray();

        $customerBalance = $this->partyService->getPartyBalance(
    $customerIds,
    $fromDate,
    $toDate
);

$supplierBalance = $this->partyService->getPartyBalance(
    $supplierIds,
    $fromDate,
    $toDate
);

        return [
            'payable' => abs($supplierBalance['balance']),
            'receivable' => abs($customerBalance['balance']),
        ];

    }

    public function getLowStockItemRecords()
    {
        return Item::with([
            'baseUnit',
            'brand',
            'category',
            'itemGeneralQuantities.warehouse',
        ])
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0)
            ->orderByDesc('current_stock')
            ->limit(10)->get();
    }

    private function partyQueryByVendorRole(string $role): Builder
    {
        $role = strtolower(trim($role));
        $vendorTypes = $role === 'supplier' ? ['supplier', 'both'] : ['customer', 'both'];

        return Party::query()->whereIn('vendor_type', $vendorTypes);
    }

    private function applyDashboardOwnershipFilter(
        Builder $query,
        ?string $viewOthersPermission = null,
        string $createdByColumn = 'created_by'
    ): Builder {
        if (! $this->shouldApplyDashboardSelfOnlyFilter($viewOthersPermission)) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($createdByColumn) {
            $subQuery->where($createdByColumn, auth()->id())
                ->orWhereNull($createdByColumn);
        });
    }

    private function shouldApplyDashboardSelfOnlyFilter(?string $viewOthersPermission = null): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->can('dashboard.can.view.self.dashboard.details.only')) {
            return false;
        }

        if ($viewOthersPermission && $user->can($viewOthersPermission)) {
            return false;
        }

        return true;
    }
}
