<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class BillController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly BillingService $billing)
    {
    }

    public function show(Bill $bill): JsonResponse
    {
        $bill->load('restaurantOrder.lines.menuItem', 'payments');

        return response()->json(['data' => $bill]);
    }

    public function pay(Request $request, Bill $bill): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string'],
            'cashier_shift_id' => ['nullable', 'integer'],
        ]);

        $cashierId = (int) $request->attributes->get('auth_user_id', 0);
        $data['idempotency_key'] = (string) $request->header('Idempotency-Key', '');

        try {
            $payment = $this->billing->pay(
                $bill,
                $data,
                $cashierId,
                isset($data['cashier_shift_id']) ? (int) $data['cashier_shift_id'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $payment], 201);
    }
}
