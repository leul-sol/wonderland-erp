<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Leave\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaveService
{
    private const PAID_LEAVE_CODES = [
        'annual' => 'AL',
        'sick' => 'SL',
        'maternity' => 'ML',
    ];

    public function __construct(
        private readonly OutboxService $outbox,
        private readonly LeaveBalanceService $leaveBalances,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LeaveRequest
    {
        $employee = Employee::query()->findOrFail($data['employee_id']);

        if ($employee->status !== 'active') {
            throw new InvalidArgumentException('Leave requests require an active employee.');
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        if ($startDate->gt($endDate)) {
            throw new InvalidArgumentException('start_date must be on or before end_date.');
        }

        $daysRequested = (int) ($data['days_requested'] ?? ($startDate->diffInDays($endDate) + 1));

        if ($daysRequested < 1) {
            throw new InvalidArgumentException('days_requested must be at least 1.');
        }

        $leaveType = $this->resolveLeaveType($data['leave_type'] ?? 'annual');

        return LeaveRequest::query()->create([
            'request_number' => $this->nextRequestNumber(),
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType?->id,
            'leave_type' => $data['leave_type'],
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days_requested' => $daysRequested,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ])->load('employee.department');
    }

    public function approve(LeaveRequest $request, int $approvedBy): LeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new InvalidArgumentException('Only pending leave requests can be approved.');
        }

        return DB::transaction(function () use ($request, $approvedBy) {
            $request->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
                'rejection_reason' => null,
            ]);

            $request->load('employee.department');
            $employee = $request->employee;
            $code = self::PAID_LEAVE_CODES[$request->leave_type] ?? null;

            if ($employee !== null && $code !== null) {
                $employee->update(['status' => 'on_leave']);
                $this->leaveBalances->deductDays($employee, $code, (float) $request->days_requested);
            }

            $this->outbox->enqueue(config('events.channels.leave_approved'), [
                'leave_request_id' => $request->id,
                'request_number' => $request->request_number,
                'employee_id' => $request->employee_id,
                'leave_type' => $request->leave_type,
                'leave_type_code' => $code ?? strtoupper(substr($request->leave_type, 0, 2)),
                'start_date' => $request->start_date?->toDateString(),
                'end_date' => $request->end_date?->toDateString(),
                'days_requested' => $request->days_requested,
            ]);

            return $request->fresh(['employee.department']);
        });
    }

    public function reject(LeaveRequest $leaveRequest, ?string $reason = null): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new InvalidArgumentException('Only pending leave requests can be rejected.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $leaveRequest->fresh(['employee.department']);
    }

    public function cancel(LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new InvalidArgumentException('Only pending leave requests can be cancelled.');
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return $leaveRequest->fresh(['employee.department']);
    }

    private function resolveLeaveType(string $leaveType): ?LeaveType
    {
        $code = self::PAID_LEAVE_CODES[$leaveType] ?? strtoupper(substr($leaveType, 0, 2));

        return LeaveType::query()->where('code', $code)->first();
    }

    private function nextRequestNumber(): string
    {
        $last = LeaveRequest::query()->orderByDesc('id')->value('request_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/LR-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'LR-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
