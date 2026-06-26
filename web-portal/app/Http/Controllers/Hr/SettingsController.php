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

class SettingsController extends Controller
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
            $leaveTypes = $this->auth->hasAnyPermission(['S2.workforce.leave_types.read'])
                ? $this->s2->leaveTypes()
                : ['data' => []];
            $overtimeRates = $this->auth->hasAnyPermission(['S2.workforce.overtime.read'])
                ? $this->s2->overtimeRates()
                : ['data' => []];
            $assetTypes = $this->auth->hasAnyPermission(['S2.hr.assets.read'])
                ? $this->s2->assetTypes()
                : ['data' => []];
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.employees.index');
        }

        return Inertia::render('Hr/Settings/Index', [
            'leaveTypes' => $leaveTypes['data'] ?? [],
            'overtimeRates' => $overtimeRates['data'] ?? [],
            'assetTypes' => $assetTypes['data'] ?? [],
            'canReadLeaveTypes' => $this->auth->hasAnyPermission(['S2.workforce.leave_types.read']),
            'canReadOvertimeRates' => $this->auth->hasAnyPermission(['S2.workforce.overtime.read']),
            'canUpdateOvertimeRates' => $this->auth->hasAnyPermission(['S2.workforce.overtime.update']),
            'canReadAssetTypes' => $this->auth->hasAnyPermission(['S2.hr.assets.read']),
            'canWriteAssetTypes' => $this->auth->hasAnyPermission(['S2.hr.assets.write']),
        ]);
    }

    public function updateOvertimeRate(Request $request, int $overtimeRate): RedirectResponse
    {
        $data = $request->validate([
            'multiplier' => ['required', 'numeric', 'min:1', 'max:5'],
        ]);

        try {
            $this->s2->updateOvertimeRate($overtimeRate, [
                'multiplier' => (float) $data['multiplier'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Overtime rate updated.');
    }

    public function storeAssetType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s2->createAssetType([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Asset type created.');
    }

    public function updateAssetType(Request $request, int $assetType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s2->updateAssetType($assetType, [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Asset type updated.');
    }

    public function destroyAssetType(int $assetType): RedirectResponse
    {
        try {
            $this->s2->deleteAssetType($assetType);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Asset type deleted.');
    }
}
