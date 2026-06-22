<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StorePayrollRunRequest;
use App\Models\PayrollRun;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PayrollRunController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly PayrollService $payroll)
    {
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

    public function approve(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        try {
            $run = $this->payroll->approve($payrollRun, (int) $request->attributes->get('auth_user_id', 0));
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('UPSTREAM_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $this->payrollRunPayload($run)]);
    }
}
