<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDepartmentLeaveScope;
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
    use AppliesDepartmentLeaveScope;
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly LeaveService $leave)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopedLeaveQuery($request);

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

    public function show(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        try {
            $this->assertLeaveInScope($leaveRequest, $request);
        } catch (InvalidArgumentException $e) {
            return $this->error('FORBIDDEN', $e->getMessage(), 403);
        }

        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $this->assertLeaveInScope($leaveRequest, $request);
            $leaveRequest = $this->leave->approve($leaveRequest, $userId);
        } catch (InvalidArgumentException $e) {
            return $this->leaveScopeError($e);
        }

        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)]);
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        try {
            $this->assertLeaveInScope($leaveRequest, $request);
            $leaveRequest = $this->leave->reject($leaveRequest, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->leaveScopeError($e);
        }

        return response()->json(['data' => $this->leaveRequestPayload($leaveRequest)]);
    }

    private function leaveScopeError(InvalidArgumentException $e): JsonResponse
    {
        $code = str_contains($e->getMessage(), 'department scope') ? 'FORBIDDEN' : 'VALIDATION_ERROR';
        $status = $code === 'FORBIDDEN' ? 403 : 422;

        return $this->error($code, $e->getMessage(), $status);
    }
}
