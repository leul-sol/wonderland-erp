<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
    ) {
    }

    public function payslip(int $employee, int $payrollRun): StreamedResponse|RedirectResponse
    {
        try {
            $response = $this->s2->downloadPayslip($employee, $payrollRun);
        } catch (ApiException $e) {
            return redirect()
                ->route('hr.employees.show', ['employee' => $employee, 'tab' => 'payslips'])
                ->with('error', $e->getMessage());
        }

        $filename = "payslip-{$employee}-{$payrollRun}.pdf";
        $disposition = $response->header('Content-Disposition') ?: 'inline; filename="'.$filename.'"';

        return response()->streamDownload(function () use ($response): void {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/pdf',
            'Content-Disposition' => $disposition,
        ]);
    }

    public function guarantorLetter(int $employee, int $guarantor): StreamedResponse|RedirectResponse
    {
        try {
            $response = $this->s2->downloadGuarantorLetter($employee, $guarantor);
        } catch (ApiException $e) {
            return redirect()
                ->route('hr.employees.show', ['employee' => $employee, 'tab' => 'guarantors'])
                ->with('error', $e->getMessage());
        }

        $filename = "guarantor-{$guarantor}.pdf";
        $disposition = $response->header('Content-Disposition') ?: 'inline; filename="'.$filename.'"';

        return response()->streamDownload(function () use ($response): void {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/pdf',
            'Content-Disposition' => $disposition,
        ]);
    }
}
