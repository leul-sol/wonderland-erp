<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreAttendanceRecordRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = AttendanceRecord::query()->with('employee.department')->orderByDesc('work_date');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        if ($request->filled('work_date')) {
            $query->whereDate('work_date', $request->string('work_date'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($record) => $this->attendancePayload($record))->values(),
        ]);
    }

    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        try {
            $record = $this->attendance->record($request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->attendancePayload($record)], 201);
    }
}
