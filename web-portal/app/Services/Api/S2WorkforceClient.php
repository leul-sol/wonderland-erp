<?php

namespace App\Services\Api;

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
     * @return array<string, mixed>
     */
    public function departments(): array
    {
        return $this->json('GET', '/s2/api/v1/departments');
    }

    /**
     * @return array<string, mixed>
     */
    public function positions(): array
    {
        return $this->json('GET', '/s2/api/v1/positions');
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
