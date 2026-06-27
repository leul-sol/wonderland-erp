<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $query = [];

        if ($request->filled('work_date')) {
            $query['work_date'] = $request->string('work_date');
        }

        return Inertia::render('Hr/Attendance/Index', [
            'filterDate' => $request->input('work_date', now()->toDateString()),
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.attendance.create']),
            'pageLoad' => $this->deferPageLoad(function () use ($query) {
                $records = $this->s2->attendanceRecords($query);
                $employees = $this->s2->employees('active');

                return [
                    'records' => $records['data'] ?? [],
                    'employees' => $employees['data'] ?? [],
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'work_date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'hours_worked' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'status' => ['nullable', 'in:present,absent,leave,half_day'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->s2->createAttendanceRecord([
                'employee_id' => (int) $data['employee_id'],
                'work_date' => $data['work_date'],
                'check_in' => $data['check_in'] ?? null,
                'check_out' => $data['check_out'] ?? null,
                'hours_worked' => isset($data['hours_worked']) ? (float) $data['hours_worked'] : null,
                'status' => $data['status'] ?? 'present',
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Attendance recorded.');
    }
}
