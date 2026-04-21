<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Fetch logged-in customer's products
     * URL: GET /api/products/my-products/{party_id}
     */
    public function myProducts($party_id)
    {
        try {
            $products = DB::table('products')
                ->where('party_id', $party_id)
                ->orderBy('id', 'desc')
                ->get([
                    'id',
                    'product_name',
                    'model_number',
                    'serial_number',
                    'purchase_order_no',
                    'purchase_order_date',
                    'tax_invoice_no',
                    'tax_invoice_date',
                    'installation_date',
                    'warranty_start',
                    'warranty_end',
                    'installed_by',
                    'remarks',
                    'product_image',
                ]);

            return response()->json([
                'status' => true,
                'data' => $products,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
