<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class PayslipController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly PayslipService $payslips)
    {
    }

    public function show(Request $request, Employee $employee, PayrollRun $payrollRun): Response
    {
        try {
            $pdf = $this->payslips->render($employee, $payrollRun);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payslip-'.$employee->employee_number.'-'.$payrollRun->run_number.'.pdf"',
        ]);
    }
}
