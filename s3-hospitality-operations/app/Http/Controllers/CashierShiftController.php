<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\CashierShift;
use App\Services\CashierShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CashierShiftController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly CashierShiftService $shifts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = CashierShift::query()->orderByDesc('opened_at');

        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', (int) $request->input('cashier_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->paginate(25)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'opening_cash_float' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $cashierId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $shift = $this->shifts->open($cashierId, isset($data['opening_cash_float']) ? (float) $data['opening_cash_float'] : null);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $shift], 201);
    }

    public function show(CashierShift $cashierShift): JsonResponse
    {
        return response()->json(['data' => $cashierShift]);
    }

    public function close(Request $request, CashierShift $cashierShift): JsonResponse
    {
        $data = $request->validate([
            'closing_cash_counted' => ['required', 'numeric', 'gte:0'],
        ]);

        try {
            $shift = $this->shifts->close($cashierShift, (float) $data['closing_cash_counted']);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $shift]);
    }

    public function report(CashierShift $cashierShift): JsonResponse
    {
        return response()->json([
            'data' => [
                'shift' => $cashierShift,
                'expected_cash' => $this->shifts->calculateExpectedCash($cashierShift),
            ],
        ]);
    }
}
