<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Employee;
use App\Models\LoanRecord;
use App\Services\IdempotencyService;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class LoanController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(
        private readonly LoanService $loans,
        private readonly IdempotencyService $idempotency,
    ) {
    }

    public function index(Employee $employee): JsonResponse
    {
        $loans = LoanRecord::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $loans->map(fn ($l) => $this->loanPayload($l))->values(),
        ]);
    }

    public function store(StoreLoanRequest $request, Employee $employee): JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if ($key === null || $key === '') {
            return $this->error('VALIDATION_ERROR', 'Idempotency-Key header is required.', 422);
        }

        $endpoint = 'POST /api/v1/employees/'.$employee->id.'/loans';
        $hash = $this->idempotency->requestHash($request);
        $replay = $this->idempotency->findReplay($key, $endpoint, $hash);

        if ($replay !== null) {
            return $this->idempotency->replayResponse($replay);
        }

        try {
            $loan = $this->loans->disburse($employee, $request->validated(), $key);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error('UPSTREAM_ERROR', $e->getMessage(), 502);
        }

        $body = ['data' => $this->loanPayload($loan)];
        $this->idempotency->store($key, $endpoint, $hash, $body, 201);

        return response()->json($body, 201);
    }
}
