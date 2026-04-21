<?php

namespace App\Http\Controllers\Api\Engineer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Items\Item;

class ItemController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->q;

        if (!$q) {
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        }

        $items = Item::where('name', 'LIKE', "%$q%")
            ->orWhere('item_code', 'LIKE', "%$q%")
            ->limit(15)
            ->get(['id','name','item_code']);

        return response()->json([
            'status' => true,
            'data' => $items
        ]);
    }
}