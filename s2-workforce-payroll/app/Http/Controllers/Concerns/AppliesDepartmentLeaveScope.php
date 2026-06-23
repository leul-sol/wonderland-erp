<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesDepartmentLeaveScope
{
    use AppliesDepartmentScope;

    /**
     * @return Builder<LeaveRequest>
     */
    protected function scopedLeaveQuery(Request $request): Builder
    {
        $query = LeaveRequest::query()->with('employee.department')->orderByDesc('id');

        return $this->scopeQueryByEmployeeDepartment($query, $request);
    }

    protected function assertLeaveInScope(LeaveRequest $leaveRequest, Request $request): void
    {
        $leaveRequest->loadMissing('employee');

        if ($leaveRequest->employee !== null) {
            $this->assertEmployeeInScope($leaveRequest->employee, $request);
        }
    }
}
