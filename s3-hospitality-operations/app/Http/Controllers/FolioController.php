<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Http\Requests\SettleFolioRequest;
use App\Http\Requests\StoreFolioChargeRequest;
use App\Models\Folio;
use App\Services\FolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class FolioController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function __construct(private readonly FolioService $folios)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Folio::query()->with('lines')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->paginate(min((int) $request->input('per_page', 25), 100)),
        ]);
    }

    public function show(Folio $folio): JsonResponse
    {
        return response()->json(['data' => $this->folioPayload($folio)]);
    }

    public function addCharge(StoreFolioChargeRequest $request, Folio $folio): JsonResponse
    {
        try {
            $this->folios->addCharge(
                $folio,
                $request->validated('description'),
                (float) $request->validated('amount'),
                $request->validated('charge_category') ?? 'room',
            );
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('UPSTREAM_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $this->folioPayload($folio->fresh('lines'))], 201);
    }

    public function recordPayment(Request $request, Folio $folio): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string'],
            'cashier_shift_id' => ['required', 'integer', 'exists:cashier_shifts,id'],
        ]);

        $data['idempotency_key'] = (string) $request->header('Idempotency-Key', '');
        $cashierId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $payment = $this->folios->recordPayment($folio, $data, $cashierId);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('UPSTREAM_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $payment], 201);
    }

    public function settle(SettleFolioRequest $request, Folio $folio): JsonResponse
    {
        try {
            $folio = $this->folios->settle(
                $folio,
                (float) $request->validated('amount'),
                $request->validated('payment_method') ?? 'cash',
            );
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('UPSTREAM_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $this->folioPayload($folio)]);
    }

    public function invoice(Folio $folio): JsonResponse
    {
        return response()->json(['data' => $this->folios->invoicePayload($folio)]);
    }
}
