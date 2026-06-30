<?php

namespace App\Http\Controllers;

use App\Exceptions\ClosedPeriodException;
use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidJournalStateException;
use App\Exceptions\UnbalancedJournalException;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesJournalEntries;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\JournalEntry;
use App\Services\JournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesJournalEntries;

    public function __construct(private readonly JournalService $journals)
    {
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        $payload = $request->validated();
        $createdBy = $request->attributes->get('auth_via_service_key')
            ? 0
            : (int) $request->attributes->get('auth_user_id', 0);

        try {
            $entry = $this->journals->post($payload, $idempotencyKey, $createdBy);
            $status = $entry->replayed ? 200 : 201;
        } catch (UnbalancedJournalException $e) {
            return $this->error('UNBALANCED_JOURNAL', $e->getMessage(), 422);
        } catch (ClosedPeriodException $e) {
            return $this->error('UNPROCESSABLE', $e->getMessage(), 422);
        } catch (IdempotencyConflictException $e) {
            return $this->error('IDEMPOTENCY_KEY_CONFLICT', $e->getMessage(), 409);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 400);
        } catch (\RuntimeException $e) {
            return $this->error('UNPROCESSABLE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->journalPayload($entry)], $status);
    }

    public function index(Request $request): JsonResponse
    {
        $query = JournalEntry::query()->with(['lines.account', 'fiscalPeriod']);

        if ($request->filled('source_module')) {
            $query->where('source_module', $request->string('source_module'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('fiscal_period_id')) {
            $query->where('fiscal_period_id', (int) $request->input('fiscal_period_id'));
        }

        $paginator = $query->orderByDesc('id')->paginate(
            min((int) $request->input('per_page', 25), 100)
        );

        return response()->json([
            'data' => $paginator->getCollection()->map(fn ($e) => $this->journalPayload($e))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        return response()->json(['data' => $this->journalPayload($journalEntry)]);
    }

    public function approve(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);
        $roles = $request->attributes->get('auth_roles', []);

        try {
            $entry = $this->journals->approve($journalEntry, $userId, is_array($roles) ? $roles : []);
        } catch (InvalidJournalStateException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        } catch (ClosedPeriodException $e) {
            return $this->error('UNPROCESSABLE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->journalPayload($entry)]);
    }

    public function postApproved(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        try {
            $entry = $this->journals->postApproved($journalEntry);
        } catch (InvalidJournalStateException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        } catch (ClosedPeriodException $e) {
            return $this->error('UNPROCESSABLE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->journalPayload($entry)]);
    }

    public function destroy(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $this->journals->deleteDraft($journalEntry, $userId);
        } catch (InvalidJournalStateException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => ['deleted' => true]]);
    }

    public function reverse(Request $request, JournalEntry $journalEntry): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);
        $reason = $request->input('reason');

        try {
            $entry = $this->journals->reverse($journalEntry, $userId, is_string($reason) ? $reason : null);
        } catch (InvalidJournalStateException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        } catch (ClosedPeriodException $e) {
            return $this->error('UNPROCESSABLE', $e->getMessage(), 422);
        } catch (UnbalancedJournalException $e) {
            return $this->error('UNBALANCED_JOURNAL', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->journalPayload($entry)], 201);
    }
}
