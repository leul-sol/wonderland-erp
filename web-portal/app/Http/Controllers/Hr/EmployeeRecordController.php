<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeRecordController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
    ) {
    }

    public function storeDisciplinary(Request $request, int $employee): RedirectResponse
    {
        $data = $request->validate([
            'action_type' => ['required', 'in:oral_warning,first_written_warning,final_written_warning,suspension,termination,immediate_dismissal'],
            'reason' => ['required', 'string', 'max:2000'],
            'effective_date' => ['required', 'date'],
            'suspension_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        try {
            $this->s2->createDisciplinaryRecord($employee, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Disciplinary record added.');
    }

    public function storeAsset(Request $request, int $employee): RedirectResponse
    {
        $data = $request->validate([
            'asset_type_id' => ['required', 'integer'],
            'serial_number' => ['nullable', 'string', 'max:80'],
            'assigned_date' => ['nullable', 'date'],
        ]);

        try {
            $this->s2->assignEmployeeAsset($employee, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Asset assigned.');
    }

    public function returnAsset(Request $request, int $employee, int $asset): RedirectResponse
    {
        $data = $request->validate([
            'returned_date' => ['nullable', 'date'],
            'condition_on_return' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s2->returnEmployeeAsset($asset, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Asset marked as returned.');
    }

    public function storeGuarantor(Request $request, int $employee): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'national_id' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $this->s2->createGuarantor($employee, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Guarantor registered. Letter PDF is available for download.');
    }

    public function storeLoan(Request $request, int $employee): RedirectResponse
    {
        $data = $request->validate([
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'monthly_repayment' => ['required', 'numeric', 'min:1'],
            'disbursed_at' => ['nullable', 'date'],
        ]);

        try {
            $this->s2->createLoan($employee, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Staff loan disbursed.');
    }
}
