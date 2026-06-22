<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\SettleReceivableRequest;
use App\Models\Receivable;
use App\Services\ReceivableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly ReceivableService $receivables)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Receivable::query()->with('account');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('source_module')) {
            $query->where('source_module', $request->string('source_module'));
        }

        $paginator = $query->orderByDesc('id')->paginate(
            min((int) $request->input('per_page', 25), 100)
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($r) => $this->receivablePayload($r))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function settle(SettleReceivableRequest $request, Receivable $receivable): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $updated = $this->receivables->settle(
                $receivable,
                (float) $request->validated('amount'),
                $request->validated('payment_method'),
                $userId
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->receivablePayload($updated)]);
    }

    private function receivablePayload(Receivable $receivable): array
    {
        $receivable->loadMissing('account');

        return [
            'id' => $receivable->id,
            'account_id' => $receivable->account_id,
            'account_code' => $receivable->account?->code,
            'party_name' => $receivable->party_name,
            'source_reference' => $receivable->source_reference,
            'source_module' => $receivable->source_module,
            'original_amount' => (string) $receivable->original_amount,
            'balance' => (string) $receivable->balance,
            'status' => $receivable->status,
            'journal_entry_id' => $receivable->journal_entry_id,
            'settled_at' => $receivable->settled_at?->toIso8601String(),
        ];
    }
}
