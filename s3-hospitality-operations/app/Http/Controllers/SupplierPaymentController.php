<?php

namespace App\Http\Controllers;

use App\Models\SupplierPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SupplierPayment::query()
            ->with('supplier')
            ->orderByDesc('payment_date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('payment_date', '>=', $request->string('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('payment_date', '<=', $request->string('to'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (SupplierPayment $payment) => [
                'id' => $payment->id,
                'supplier_id' => $payment->supplier_id,
                'supplier_name' => $payment->supplier?->name,
                'amount' => (string) $payment->amount,
                'payment_method' => $payment->payment_method,
                'payment_date' => $payment->payment_date?->toDateString(),
                'reference_number' => $payment->reference_number,
                'posted_to_finance' => $payment->posted_to_finance,
            ])->values(),
        ]);
    }
}
