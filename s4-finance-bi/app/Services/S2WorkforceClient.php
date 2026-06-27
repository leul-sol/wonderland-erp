<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class S2WorkforceClient
{
    public function __construct(private readonly IntegrationCacheService $cache)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function employees(): array
    {
        return $this->cachedGet('s2.employees', 'employees', config('services.cache_ttl.employees'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function payrollRuns(): array
    {
        return $this->cachedGet('s2.payroll_runs', 'payroll-runs', config('services.cache_ttl.payroll'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function leaveRequests(): array
    {
        return $this->cachedGet('s2.leave_requests', 'leave-requests', config('services.cache_ttl.employees'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function attendanceRecords(): array
    {
        return $this->cachedGet('s2.attendance_records', 'attendance-records', config('services.cache_ttl.employees'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function overtimeRecords(): array
    {
        return $this->cachedGet('s2.overtime_records', 'overtime-records', config('services.cache_ttl.payroll'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function offboardingRecords(): array
    {
        return $this->cachedGet('s2.offboarding_records', 'offboarding-records', config('services.cache_ttl.employees'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function employeeDisciplinaryRecords(int $employeeId): array
    {
        return $this->fetchList('employees/'.$employeeId.'/disciplinary-records');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function employeeGuarantors(int $employeeId): array
    {
        return $this->fetchList('employees/'.$employeeId.'/guarantors');
    }

    public function payslipPdf(int $employeeId, int $payrollRunId): string
    {
        $url = rtrim((string) config('services.s2_url'), '/').'/api/v1/employees/'.$employeeId.'/payslip/'.$payrollRunId;

        $response = Http::withHeaders($this->headers())
            ->timeout(15)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('S2 payslip returned HTTP '.$response->status());
        }

        return $response->body();
    }

    public function guarantorLetterPdf(int $employeeId, int $guarantorId): string
    {
        $url = rtrim((string) config('services.s2_url'), '/').'/api/v1/employees/'.$employeeId.'/guarantors/'.$guarantorId.'/letter';

        $response = Http::withHeaders($this->headers())
            ->timeout(15)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('S2 guarantor letter returned HTTP '.$response->status());
        }

        return $response->body();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cachedGet(string $cacheKey, string $path, int $ttl): array
    {
        return $this->cache->remember($cacheKey, $ttl, function () use ($path) {
            $url = rtrim((string) config('services.s2_url'), '/').'/api/v1/'.$path;

            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->timeout(10)
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('S2 '.$path.' returned HTTP '.$response->status());
            }

            $data = $response->json('data');

            return is_array($data) ? $data : [];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchList(string $path): array
    {
        $url = rtrim((string) config('services.s2_url'), '/').'/api/v1/'.$path;

        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->timeout(10)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('S2 '.$path.' returned HTTP '.$response->status());
        }

        $data = $response->json('data');

        return is_array($data) ? array_values($data) : [];
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'X-Service-Key' => (string) config('services.internal_key_current'),
        ];
    }
}
