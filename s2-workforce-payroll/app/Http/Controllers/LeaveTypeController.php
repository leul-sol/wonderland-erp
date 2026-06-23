<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
    use SerializesWorkforceResources;

    public function index(): JsonResponse
    {
        $types = LeaveType::query()->orderBy('code')->get();

        return response()->json([
            'data' => $types->map(fn ($t) => $this->leaveTypePayload($t))->values(),
        ]);
    }

    public function show(LeaveType $leaveType): JsonResponse
    {
        return response()->json(['data' => $this->leaveTypePayload($leaveType)]);
    }
}
