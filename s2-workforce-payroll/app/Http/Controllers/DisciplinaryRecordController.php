<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDepartmentScope;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreDisciplinaryRecordRequest;
use App\Models\Employee;
use App\Services\DisciplinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DisciplinaryRecordController extends Controller
{
    use AppliesDepartmentScope;
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly DisciplinaryService $disciplinary)
    {
    }

    public function index(Request $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        $records = $employee->disciplinaryRecords()->orderByDesc('effective_date')->get();

        return response()->json([
            'data' => $records->map(fn ($r) => $this->disciplinaryPayload($r))->values(),
        ]);
    }

    public function store(StoreDisciplinaryRecordRequest $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
            $record = $this->disciplinary->record(
                $employee,
                $request->validated(),
                (int) $request->attributes->get('auth_user_id', 0),
            );
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->disciplinaryPayload($record)], 201);
    }
}
