<?php

namespace App\Console\Commands;

use App\Services\EmployeeEventService;
use App\Services\S2WorkforceClient;
use Illuminate\Console\Command;
use RuntimeException;

class ProvisionEmployeeFromS2 extends Command
{
    protected $signature = 'employees:provision-from-s2 {employee_id : S2 employee id}';

    protected $description = 'Provision an S1 user from the current S2 employee record (event-bus catch-up)';

    public function handle(S2WorkforceClient $s2, EmployeeEventService $employees): int
    {
        $employeeId = (int) $this->argument('employee_id');

        try {
            $response = $s2->getEmployee($employeeId);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $employee = is_array($response['data'] ?? null) ? $response['data'] : [];

        $employees->handleCreated([
            'employee_id' => (int) ($employee['id'] ?? $employeeId),
            'full_name' => (string) ($employee['full_name'] ?? 'Employee'),
            'department_id' => $employee['department_id'] ?? null,
            'default_role' => (string) ($employee['default_role'] ?? 'report_viewer'),
        ]);

        $this->info("Provisioned S1 user for employee {$employeeId}.");

        return self::SUCCESS;
    }
}
