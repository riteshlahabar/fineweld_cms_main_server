<?php

namespace App\Http\Controllers\Supportportal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportPortal\Product;
use Carbon\Carbon;
use App\Models\Party\Party;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    /**
     * Product list page
     */
    public function list()
    {
        return view('support-portal.products.list');
    }

    /**
     * Datatable JSON
     */

    public function datatableList(Request $request)
    {
        $products = Product::with('party')
            ->select(
    'id',
    'party_id',
    'product_image',
                // Purchase details
                'purchase_order_no',
                'purchase_order_date',
                'tax_invoice_no',
                'tax_invoice_date',

                // Product details
                'product_name',
                'model_number',
                'serial_number',

                // Installation & warranty
                'installation_date',
                'warranty_start',
                'warranty_end',

                // Meta
                'installed_by',
                'remarks'
            )
            ->latest()
            ->get();

        return response()->json([
            'data' => $products->map(function ($p) {

                // ✅ WARRANTY REMAINING (DAYS)
                if ($p->warranty_end) {
                    $days = Carbon::now()->startOfDay()
                        ->diffInDays(Carbon::parse($p->warranty_end)->startOfDay(), false);

                    $warrantyRemaining = $days > 0
                        ? "<span class='text-success fw-bold'>{$days} days</span>"
                        : "<span class='text-danger fw-bold'>Expired</span>";
                } else {
                    $warrantyRemaining = '-';
                }

                return [
                    'id' => $p->id,

                    // PARTY
                    'company_name' => optional($p->party)->company_name ?? '-',
                    'primary_name' => optional($p->party)->primary_name ?? '-',
                    'primary_mobile' => optional($p->party)->primary_mobile ?? '-',

                    // PURCHASE
                    'purchase_order_no' => $p->purchase_order_no ?? '-',
                    'purchase_order_date' => $p->purchase_order_date ?? '-',
                    'tax_invoice_no' => $p->tax_invoice_no ?? '-',
                    'tax_invoice_date' => $p->tax_invoice_date ?? '-',

                    // PRODUCT
                    'product_name' => $p->product_name ?? '-',
                    'product_image' => $p->product_image
    ? asset($p->product_image)
    : null,

                    'model_number' => $p->model_number ?? '-',
                    'serial_number' => $p->serial_number ?? '-',

                    // INSTALLATION
                    'installation_date' => $p->installation_date ?? '-',
                    'warranty_start' => $p->warranty_start ?? '-',
                    'warranty_end' => $p->warranty_end ?? '-',
                    'warranty_remaining' => $warrantyRemaining,

                    'installed_by' => $p->installed_by ?? '-',
                    'remarks' => $p->remarks ?? '-',

                    // ACTION (placeholder)
                    'action' => '<a href="#" class="btn btn-sm btn-warning">Edit</a>
                             <a href="#" class="btn btn-sm btn-danger">Delete</a>',
                ];
            })
        ]);
    }
    public function create()
    {
        // Fetch only customers / both
        $parties = Party::whereIn('vendor_type', ['customer', 'both'])
            ->orderBy('company_name')
            ->get();

        return view('support-portal.products.create', compact('parties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'party_id' => 'required|exists:parties,id',
            'product_name' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:products,serial_number',
            'product_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $parseDate = function ($date) {
            return $date
                ? Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d')
                : null;
        };

        // ✅ IMAGE UPLOAD
        $imagePath = null;
        if ($request->hasFile('product_image')) {

            $uploadPath = public_path('uploads/product_image');

            // auto create folder
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }

            $image = $request->file('product_image');
            $imageName = 'product_' . time() . '.' . $image->getClientOriginalExtension();

            $image->move($uploadPath, $imageName);

            $imagePath = 'uploads/product_image/' . $imageName;
        }

        Product::create([
            'party_id' => $request->party_id,

            'purchase_order_no' => $request->purchase_order_no,
            'purchase_order_date' => $parseDate($request->purchase_order_date),

            'tax_invoice_no' => $request->tax_invoice_no,
            'tax_invoice_date' => $parseDate($request->tax_invoice_date),

            'product_name' => $request->product_name,
            'model_number' => $request->model_number,
            'serial_number' => $request->serial_number,

            'installation_date' => $parseDate($request->installation_date),
            'warranty_start' => $parseDate($request->warranty_start),
            'warranty_end' => $parseDate($request->warranty_end),

            'installed_by' => $request->installed_by,
            'remarks' => $request->remarks,

            'product_image' => $imagePath,
        ]);

        return redirect()
            ->route('products.list')
            ->with('success', 'Product registered successfully');
    }
}
