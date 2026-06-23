<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StorePayrollRunRequest;
use App\Models\PayrollRun;
use App\Services\IdempotencyService;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PayrollRunController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(
        private readonly PayrollService $payroll,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = PayrollRun::query()->with('lines.employee')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($run) => $this->payrollRunPayload($run))->values(),
        ]);
    }

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        try {
            $run = $this->payroll->createRun($request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->payrollRunPayload($run)], 201);
    }

    public function show(PayrollRun $payrollRun): JsonResponse
    {
        return response()->json(['data' => $this->payrollRunPayload($payrollRun)]);
    }

    public function submit(PayrollRun $payrollRun): JsonResponse
    {
        try {
            $run = $this->payroll->submit($payrollRun);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->payrollRunPayload($run)]);
    }

    public function approve(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if ($key === null || $key === '') {
            return $this->error('VALIDATION_ERROR', 'Idempotency-Key header is required.', 422);
        }

        $endpoint = 'POST /api/v1/payroll-runs/'.$payrollRun->id.'/approve';
        $hash = $this->idempotency->requestHash($request);
        $replay = $this->idempotency->findReplay($key, $endpoint, $hash);

        if ($replay !== null) {
            return $this->idempotency->replayResponse($replay);
        }

        try {
            $run = $this->payroll->approve($payrollRun, (int) $request->attributes->get('auth_user_id', 0));
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('UPSTREAM_ERROR', $e->getMessage(), 502);
        }

        $body = ['data' => $this->payrollRunPayload($run)];
        $this->idempotency->store($key, $endpoint, $hash, $body, 200);

        return response()->json($body);
    }

    public function lock(PayrollRun $payrollRun): JsonResponse
    {
        try {
            $run = $this->payroll->lock($payrollRun);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->payrollRunPayload($run)]);
    }
}
