<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDepartmentScope;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreOvertimeRecordRequest;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Services\OvertimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class OvertimeRecordController extends Controller
{
    use AppliesDepartmentScope;
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly OvertimeService $overtime)
    {
    }

    public function index(Request $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        $records = OvertimeRecord::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('work_date')
            ->get();

        return response()->json([
            'data' => $records->map(fn ($r) => $this->overtimeRecordPayload($r))->values(),
        ]);
    }

    public function store(StoreOvertimeRecordRequest $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
            $record = $this->overtime->record($employee, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->overtimeRecordPayload($record)], 201);
    }

    public function approve(Request $request, OvertimeRecord $overtimeRecord): JsonResponse
    {
        try {
            $overtimeRecord->loadMissing('employee');

            if ($overtimeRecord->employee !== null) {
                $this->assertEmployeeInScope($overtimeRecord->employee, $request);
            }

            $record = $this->overtime->approve($overtimeRecord);
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->overtimeRecordPayload($record)]);
    }
}
