<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $leaveRequests = $this->s2->leaveRequests();
            $employees = $this->s2->employees('active');
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Hr/Leave/Index', [
            'leaveRequests' => $leaveRequests['data'] ?? [],
            'employees' => $employees['data'] ?? [],
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.leave_requests.create']),
            'canApprove' => $this->auth->hasAnyPermission(['S2.workforce.leave_requests.approve']),
            'canReject' => $this->auth->hasAnyPermission(['S2.workforce.leave_requests.reject']),
            'canCancel' => $this->auth->hasAnyPermission(['S2.workforce.leave_requests.create']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'leave_type' => ['required', 'in:annual,sick,unpaid,maternity,other'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->s2->createLeaveRequest([
                'employee_id' => (int) $data['employee_id'],
                'leave_type' => $data['leave_type'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Leave request submitted.');
    }

    public function approve(int $leaveRequest): RedirectResponse
    {
        try {
            $this->s2->approveLeaveRequest($leaveRequest);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, int $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->s2->rejectLeaveRequest($leaveRequest, $data['reason'] ?? null);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Leave request rejected.');
    }

    public function cancel(int $leaveRequest): RedirectResponse
    {
        try {
            $this->s2->cancelLeaveRequest($leaveRequest);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Leave request cancelled.');
    }
}
