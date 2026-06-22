<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LeaveRequestController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly LeaveService $leave)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::query()->with('employee.department')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($item) => $this->leaveRequestPayload($item))->values(),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        try {
            $leaveRequest = $this->leave->create($request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)], 201);
    }

    public function show(LeaveRequest $leaveRequest): JsonResponse
    {
        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $leaveRequest = $this->leave->approve($leaveRequest, $userId);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)]);
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        try {
            $leaveRequest = $this->leave->reject($leaveRequest, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)]);
    }
}
