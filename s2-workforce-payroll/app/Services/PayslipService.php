<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollLine;
use App\Models\PayrollRun;
use App\Services\Documents\PdfDocumentService;
use InvalidArgumentException;

class PayslipService
{
    public function __construct(private readonly PdfDocumentService $pdf)
    {
    }

    public function render(Employee $employee, PayrollRun $run): string
    {
        $line = PayrollLine::query()
            ->where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->first();

        if ($line === null) {
            throw new InvalidArgumentException('No payroll line found for this employee in the selected run.');
        }

        if (! in_array($run->status, ['approved', 'locked'], true)) {
            throw new InvalidArgumentException('Payslips are only available for approved or locked payroll runs.');
        }

        $html = $this->payslipHtml($employee, $run, $line);

        return $this->pdf->render($html);
    }

    private function payslipHtml(Employee $employee, PayrollRun $run, PayrollLine $line): string
    {
        $name = htmlspecialchars($employee->full_name, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>
body{font-family:"DejaVu Sans",sans-serif;font-size:12px;}
table{width:100%;border-collapse:collapse;margin-top:16px;}
td,th{border:1px solid #ccc;padding:8px;text-align:left;}
h2{margin:0;}
</style></head>
<body>
<h2>Wonderland Hotel — Payslip / ደመወዝ ደረሰኝ</h2>
<p><strong>Employee:</strong> {$name} ({$employee->employee_number})</p>
<p><strong>Payroll run:</strong> {$run->run_number}</p>
<p><strong>Period:</strong> {$run->period_start?->toDateString()} to {$run->period_end?->toDateString()}</p>
<table>
<tr><th>Component</th><th>Amount (ETB)</th></tr>
<tr><td>Gross salary</td><td>{$line->gross_salary}</td></tr>
<tr><td>Overtime pay</td><td>{$line->overtime_pay}</td></tr>
<tr><td>Employee pension</td><td>{$line->employee_pension}</td></tr>
<tr><td>Income tax</td><td>{$line->income_tax}</td></tr>
<tr><td>Other deductions</td><td>{$line->other_deductions}</td></tr>
<tr><td>Loan repayment</td><td>{$line->loan_repayment}</td></tr>
<tr><th>Net pay</th><th>{$line->net_pay}</th></tr>
</table>
</body>
</html>
HTML;
    }
}
