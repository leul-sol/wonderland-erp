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

class OffboardingController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Hr/Offboarding/Index', [
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.offboarding.create']),
            'canUpdate' => $this->auth->hasAnyPermission(['S2.workforce.offboarding.update']),
            'pageLoad' => $this->deferPageLoad(function () {
                $records = $this->s2->offboardingRecords();
                $employees = $this->s2->employees('active');

                $offboardingEmployeeIds = collect($records['data'] ?? [])
                    ->pluck('employee_id')
                    ->filter()
                    ->all();

                $eligibleEmployees = collect($employees['data'] ?? [])
                    ->reject(fn (array $employee) => in_array($employee['id'] ?? 0, $offboardingEmployeeIds, true))
                    ->values()
                    ->all();

                return [
                    'offboardingRecords' => $records['data'] ?? [],
                    'eligibleEmployees' => $eligibleEmployees,
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'reason' => ['required', 'in:resignation,termination,retirement,end_of_contract,death'],
            'last_working_day' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'calculate_severance' => ['nullable', 'boolean'],
        ]);

        try {
            $response = $this->s2->createOffboarding((int) $data['employee_id'], [
                'reason' => $data['reason'],
                'last_working_day' => $data['last_working_day'],
                'notes' => $data['notes'] ?? null,
                'calculate_severance' => (bool) ($data['calculate_severance'] ?? false),
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $recordId = (int) ($response['data']['id'] ?? 0);

        if ($recordId <= 0) {
            return back()->with('error', 'Offboarding was not started.');
        }

        return redirect()
            ->route('hr.offboarding.show', $recordId)
            ->with('success', 'Dead file opened. Complete clearance checklist.');
    }

    public function show(int $offboarding): Response|RedirectResponse
    {
        try {
            $response = $this->s2->offboardingRecord($offboarding);
            $employeeId = (int) ($response['data']['employee_id'] ?? 0);
            $assets = $employeeId > 0 ? $this->s2->employeeAssets($employeeId) : ['data' => []];
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.offboarding.index');
        }

        $record = $response['data'] ?? [];
        $outstandingAssets = collect($assets['data'] ?? [])
            ->filter(fn (array $asset) => empty($asset['returned_date']))
            ->values()
            ->all();

        return Inertia::render('Hr/Offboarding/Show', [
            'offboardingRecord' => $record,
            'outstandingAssets' => $outstandingAssets,
            'clearanceSteps' => $this->clearanceSteps(),
            'canUpdate' => $this->auth->hasAnyPermission(['S2.workforce.offboarding.update']),
            'canReadSeverance' => $this->auth->hasAnyPermission(['S2.workforce.severance.read']),
            'canReturnAssets' => $this->auth->hasAnyPermission(['S2.hr.assets.write']),
        ]);
    }

    public function update(Request $request, int $offboarding): RedirectResponse
    {
        $data = $request->validate([
            'clearance_status' => ['nullable', 'in:pending,in_progress,completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = array_filter([
            'clearance_status' => $data['clearance_status'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($payload === []) {
            return back()->with('error', 'No changes to save.');
        }

        try {
            $this->s2->updateOffboarding($offboarding, $payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $message = ($data['clearance_status'] ?? null) === 'completed'
            ? 'Dead file completed. Employee archived.'
            : 'Offboarding record updated.';

        return back()->with('success', $message);
    }

    /**
     * @return list<array{key: string, label: string, hint: string}>
     */
    private function clearanceSteps(): array
    {
        return [
            ['key' => 'pending', 'label' => 'Opened', 'hint' => 'Dead file created'],
            ['key' => 'in_progress', 'label' => 'Clearance', 'hint' => 'Assets & HR checks'],
            ['key' => 'completed', 'label' => 'Closed', 'hint' => 'Employee archived'],
        ];
    }
}
