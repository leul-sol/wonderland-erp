<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StockController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly StockService $stock)
    {
    }

    public function adjust(Request $request): JsonResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $movement = $this->stock->adjust(
                (int) $data['inventory_item_id'],
                (float) $data['quantity'],
                (string) ($data['reason'] ?? 'Manual adjustment'),
                (int) $request->attributes->get('auth_user_id', 0),
            );
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $movement], 201);
    }

    public function writeOff(Request $request): JsonResponse
    {
        $data = $request->validate([
            'inventory_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $movement = $this->stock->writeOff(
                (int) $data['inventory_item_id'],
                (float) $data['quantity'],
                (int) $request->attributes->get('auth_user_id', 0),
            );
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $movement], 201);
    }

    public function expiryAlerts(): JsonResponse
    {
        return response()->json(['data' => $this->stock->expiryAlerts()]);
    }

    public function lowStockAlerts(): JsonResponse
    {
        return response()->json(['data' => $this->stock->lowStockAlerts()]);
    }

    public function valuation(): JsonResponse
    {
        return response()->json(['data' => $this->stock->valuation()]);
    }
}
