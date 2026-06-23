<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\GoodsReceipt;
use Illuminate\Http\JsonResponse;

class GoodsReceiptController extends Controller
{
    use RespondsWithApiErrors;

    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $goodsReceipt->load(['lines.inventoryItem', 'purchaseOrder']);

        return response()->json(['data' => $goodsReceipt]);
    }
}
