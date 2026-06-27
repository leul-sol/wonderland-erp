<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\SettlePayableRequest;
use App\Models\Payable;
use App\Services\PayableService;
use App\Support\SubledgerAging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly PayableService $payables)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payable::query()->with('account');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }

        if ($request->filled('source_module')) {
            $query->where('source_module', $request->string('source_module'));
        }

        if ($request->filled('aging_bucket')) {
            SubledgerAging::applyBucketFilter($query, (string) $request->input('aging_bucket'));
        }

        $paginator = $query->orderByDesc('id')->paginate(
            min((int) $request->input('per_page', 25), 100)
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($p) => $this->payablePayload($p))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function settle(SettlePayableRequest $request, Payable $payable): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $updated = $this->payables->settle(
                $payable,
                (float) $request->validated('amount'),
                $request->validated('payment_method'),
                $userId
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->payablePayload($updated)]);
    }

    private function payablePayload(Payable $payable): array
    {
        $payable->loadMissing('account');
        $aging = SubledgerAging::classify($payable->due_date);

        return [
            'id' => $payable->id,
            'account_id' => $payable->account_id,
            'account_code' => $payable->account?->code,
            'supplier_id' => $payable->supplier_id,
            'vendor_name' => $payable->vendor_name,
            'source_reference' => $payable->source_reference,
            'source_module' => $payable->source_module,
            'original_amount' => (string) $payable->original_amount,
            'balance' => (string) $payable->balance,
            'due_date' => $payable->due_date?->toDateString(),
            'days_overdue' => $aging['days_overdue'],
            'aging_bucket' => $aging['bucket'],
            'status' => $payable->status,
            'journal_entry_id' => $payable->journal_entry_id,
            'settled_at' => $payable->settled_at?->toIso8601String(),
        ];
    }
}
