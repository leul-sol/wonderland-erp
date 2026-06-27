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

class OvertimeController extends Controller
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
        $status = (string) $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'paid', 'all'], true)) {
            $status = 'pending';
        }

        $query = $status === 'all' ? [] : ['status' => $status];

        return Inertia::render('Hr/Overtime/Index', [
            'filterStatus' => $status,
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.overtime.create']),
            'canApprove' => $this->auth->hasAnyPermission(['S2.workforce.overtime.approve']),
            'pageLoad' => $this->deferPageLoad(function () use ($query) {
                $records = $this->s2->overtimeRecords($query);
                $employees = $this->s2->employees('active');
                $rates = $this->s2->overtimeRates();

                return [
                    'overtimeRecords' => $records['data'] ?? [],
                    'employees' => $employees['data'] ?? [],
                    'overtimeRates' => $rates['data'] ?? [],
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'work_date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'category' => ['required', 'in:working_day,sunday,holiday,night'],
        ]);

        try {
            $this->s2->createOvertimeRecord((int) $data['employee_id'], [
                'work_date' => $data['work_date'],
                'hours' => (float) $data['hours'],
                'category' => $data['category'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Overtime record submitted for approval.');
    }

    public function approve(int $overtimeRecord): RedirectResponse
    {
        try {
            $this->s2->approveOvertimeRecord($overtimeRecord);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Overtime record approved.');
    }
}
