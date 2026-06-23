<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

trait AppliesDepartmentScope
{
    protected function scopedDepartmentId(Request $request): ?int
    {
        $deptScope = $request->attributes->get('auth_dept_scope');

        if (! is_string($deptScope) || $deptScope === '') {
            return null;
        }

        return (int) $deptScope;
    }

    /**
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    protected function scopeEmployeeQuery(Builder $query, Request $request): Builder
    {
        $departmentId = $this->scopedDepartmentId($request);

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        return $query;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function scopeQueryByEmployeeDepartment(Builder $query, Request $request, string $relation = 'employee'): Builder
    {
        $departmentId = $this->scopedDepartmentId($request);

        if ($departmentId === null) {
            return $query;
        }

        return $query->whereHas($relation, fn (Builder $employee) => $employee->where('department_id', $departmentId));
    }

    protected function assertEmployeeInScope(Employee $employee, Request $request): void
    {
        $departmentId = $this->scopedDepartmentId($request);

        if ($departmentId === null) {
            return;
        }

        if ((int) ($employee->department_id ?? 0) !== $departmentId) {
            throw new InvalidArgumentException('Resource is outside your department scope.');
        }
    }

    protected function departmentScopeError(InvalidArgumentException $e): JsonResponse
    {
        $code = str_contains($e->getMessage(), 'department scope') ? 'FORBIDDEN' : 'VALIDATION_ERROR';
        $status = $code === 'FORBIDDEN' ? 403 : 422;

        return $this->error($code, $e->getMessage(), $status);
    }
}
