<?php

namespace App\Services\Api;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

class S2WorkforceClient extends GatewayClient
{
    /**
     * @return array<string, mixed>
     */
    public function employees(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s2/api/v1/employees', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function employee(int $id): array
    {
        return $this->json('GET', "/s2/api/v1/employees/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createEmployee(array $payload): array
    {
        return $this->json('POST', '/s2/api/v1/employees', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateEmployee(int $id, array $payload): array
    {
        return $this->json('PATCH', "/s2/api/v1/employees/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function departments(): array
    {
        return $this->json('GET', '/s2/api/v1/departments');
    }

    /**
     * @return array<string, mixed>
     */
    public function department(int $id): array
    {
        return $this->json('GET', "/s2/api/v1/departments/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDepartment(array $payload): array
    {
        return $this->json('POST', '/s2/api/v1/departments', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateDepartment(int $id, array $payload): array
    {
        return $this->json('PATCH', "/s2/api/v1/departments/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteDepartment(int $id): array
    {
        return $this->json('DELETE', "/s2/api/v1/departments/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function positions(): array
    {
        return $this->json('GET', '/s2/api/v1/positions');
    }

    /**
     * @return array<string, mixed>
     */
    public function position(int $id): array
    {
        return $this->json('GET', "/s2/api/v1/positions/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPosition(array $payload): array
    {
        return $this->json('POST', '/s2/api/v1/positions', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updatePosition(int $id, array $payload): array
    {
        return $this->json('PATCH', "/s2/api/v1/positions/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function deletePosition(int $id): array
    {
        return $this->json('DELETE', "/s2/api/v1/positions/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function assetTypes(): array
    {
        return $this->json('GET', '/s2/api/v1/asset-types');
    }

    /**
     * @return array<string, mixed>
     */
    public function leaveBalances(int $employeeId): array
    {
        return $this->json('GET', "/s2/api/v1/employees/{$employeeId}/leave-balances");
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function leaveRequests(array $query = []): array
    {
        return $this->json('GET', '/s2/api/v1/leave-requests', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createLeaveRequest(array $payload): array
    {
        return $this->json('POST', '/s2/api/v1/leave-requests', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function approveLeaveRequest(int $id): array
    {
        return $this->json('POST', "/s2/api/v1/leave-requests/{$id}/approve");
    }

    /**
     * @return array<string, mixed>
     */
    public function rejectLeaveRequest(int $id, ?string $reason = null): array
    {
        return $this->json('POST', "/s2/api/v1/leave-requests/{$id}/reject", array_filter([
            'reason' => $reason,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelLeaveRequest(int $id): array
    {
        return $this->json('POST', "/s2/api/v1/leave-requests/{$id}/cancel");
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function attendanceRecords(array $query = []): array
    {
        return $this->json('GET', '/s2/api/v1/attendance-records', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createAttendanceRecord(array $payload): array
    {
        return $this->json('POST', '/s2/api/v1/attendance-records', $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function overtimeRecords(array $query = []): array
    {
        return $this->json('GET', '/s2/api/v1/overtime-records', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOvertimeRecord(int $employeeId, array $payload): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/overtime-records", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function approveOvertimeRecord(int $id): array
    {
        return $this->json('POST', "/s2/api/v1/overtime-records/{$id}/approve");
    }

    /**
     * @return array<string, mixed>
     */
    public function overtimeRates(): array
    {
        return $this->json('GET', '/s2/api/v1/overtime-rates');
    }

    /**
     * @return array<string, mixed>
     */
    public function offboardingRecords(): array
    {
        return $this->json('GET', '/s2/api/v1/offboarding-records');
    }

    /**
     * @return array<string, mixed>
     */
    public function offboardingRecord(int $id): array
    {
        return $this->json('GET', "/s2/api/v1/offboarding-records/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOffboarding(int $employeeId, array $payload): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/offboarding", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateOffboarding(int $id, array $payload): array
    {
        return $this->json('PATCH', "/s2/api/v1/offboarding-records/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function disciplinaryRecords(int $employeeId): array
    {
        return $this->json('GET', "/s2/api/v1/employees/{$employeeId}/disciplinary-records");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDisciplinaryRecord(int $employeeId, array $payload): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/disciplinary-records", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeAssets(int $employeeId): array
    {
        return $this->json('GET', "/s2/api/v1/employees/{$employeeId}/assets");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function assignEmployeeAsset(int $employeeId, array $payload): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/assets", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function returnEmployeeAsset(int $assetId, array $payload = []): array
    {
        return $this->json('PUT', "/s2/api/v1/assets/{$assetId}/return", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function guarantors(int $employeeId): array
    {
        return $this->json('GET', "/s2/api/v1/employees/{$employeeId}/guarantors");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createGuarantor(int $employeeId, array $payload): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/guarantors", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function loans(int $employeeId): array
    {
        return $this->json('GET', "/s2/api/v1/employees/{$employeeId}/loans");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createLoan(int $employeeId, array $payload): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/loans", $payload, [
            'Idempotency-Key' => (string) Str::uuid(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollRuns(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s2/api/v1/payroll-runs', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollRun(int $id): array
    {
        return $this->json('GET', "/s2/api/v1/payroll-runs/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPayrollRun(array $payload): array
    {
        return $this->json('POST', '/s2/api/v1/payroll-runs', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function submitPayrollRun(int $id): array
    {
        return $this->json('POST', "/s2/api/v1/payroll-runs/{$id}/submit");
    }

    /**
     * @return array<string, mixed>
     */
    public function approvePayrollRun(int $id, string $idempotencyKey): array
    {
        return $this->json('POST', "/s2/api/v1/payroll-runs/{$id}/approve", [], [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function lockPayrollRun(int $id): array
    {
        return $this->json('POST', "/s2/api/v1/payroll-runs/{$id}/lock");
    }

    public function downloadPayslip(int $employeeId, int $payrollRunId): Response
    {
        return $this->download('GET', "/s2/api/v1/employees/{$employeeId}/payslip/{$payrollRunId}");
    }

    public function downloadGuarantorLetter(int $employeeId, int $guarantorId): Response
    {
        return $this->download('GET', "/s2/api/v1/employees/{$employeeId}/guarantors/{$guarantorId}/letter");
    }

    /**
     * @return array<string, mixed>
     */
    public function severanceCalculations(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s2/api/v1/severance-calculations', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateSeverance(int $employeeId): array
    {
        return $this->json('POST', "/s2/api/v1/employees/{$employeeId}/severance/calculate");
    }

    /**
     * @return array<string, mixed>
     */
    public function paySeverance(int $calculationId): array
    {
        return $this->json('POST', "/s2/api/v1/severance-calculations/{$calculationId}/pay");
    }
}
