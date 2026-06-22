<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use InvalidArgumentException;

trait AppliesDepartmentLeaveScope
{
    /**
     * @return Builder<LeaveRequest>
     */
    protected function scopedLeaveQuery(Request $request): Builder
    {
        $query = LeaveRequest::query()->with('employee.department')->orderByDesc('id');

        $deptScope = $request->attributes->get('auth_dept_scope');

        if (is_string($deptScope) && $deptScope !== '') {
            $query->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', (int) $deptScope));
        }

        return $query;
    }

    protected function assertLeaveInScope(LeaveRequest $leaveRequest, Request $request): void
    {
        $deptScope = $request->attributes->get('auth_dept_scope');

        if (! is_string($deptScope) || $deptScope === '') {
            return;
        }

        $leaveRequest->loadMissing('employee');

        if ((int) ($leaveRequest->employee?->department_id ?? 0) !== (int) $deptScope) {
            throw new InvalidArgumentException('Leave request is outside your department scope.');
        }
    }
}
