<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDepartmentScope;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Models\Employee;
use App\Models\LeaveBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveBalanceController extends Controller
{
    use AppliesDepartmentScope;
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function index(Request $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        $year = (int) ($request->input('year') ?? now()->format('Y'));

        $balances = LeaveBalance::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        return response()->json([
            'data' => $balances->map(fn ($b) => $this->leaveBalancePayload($b))->values(),
        ]);
    }
}
