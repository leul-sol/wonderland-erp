<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Guarantor;
use App\Services\Documents\PdfDocumentService;
use Illuminate\Support\Facades\Storage;

class GuarantorService
{
    public function __construct(private readonly PdfDocumentService $pdf)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Employee $employee, array $data): Guarantor
    {
        $guarantor = Guarantor::query()->create([
            'employee_id' => $employee->id,
            'full_name' => $data['full_name'],
            'national_id' => $data['national_id'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'relationship' => $data['relationship'] ?? null,
        ]);

        $relativePath = 'guarantors/guarantor-'.$guarantor->id.'.pdf';
        $html = $this->letterHtml($employee, $guarantor);
        Storage::disk('local')->put($relativePath, $this->pdf->render($html));

        $guarantor->update(['letter_path' => $relativePath]);

        return $guarantor->fresh('employee');
    }

    private function letterHtml(Employee $employee, Guarantor $guarantor): string
    {
        $employeeName = htmlspecialchars($employee->full_name, ENT_QUOTES, 'UTF-8');
        $guarantorName = htmlspecialchars($guarantor->full_name, ENT_QUOTES, 'UTF-8');
        $relationship = htmlspecialchars((string) ($guarantor->relationship ?? 'N/A'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><style>body{font-family:"DejaVu Sans",sans-serif;font-size:12px;line-height:1.5;}</style></head>
<body>
<h2>Wonderland Hotel — Guarantor Letter / ዋስትና ደብዳቤ</h2>
<p><strong>Employee / ሰራተኛ:</strong> {$employeeName} ({$employee->employee_number})</p>
<p><strong>Guarantor / ዋስ:</strong> {$guarantorName}</p>
<p><strong>National ID / መታወቂያ:</strong> {$guarantor->national_id}</p>
<p><strong>Phone / ስልክ:</strong> {$guarantor->phone}</p>
<p><strong>Relationship / ዝምድና:</strong> {$relationship}</p>
<p>I hereby guarantee the employee named above and accept responsibility for company property and cash handling obligations as required by hotel policy.</p>
<p>ከላይ የተጠቀሰውን ሰራተኛ ዋስትና እሰጣለሁ እና የሆቴሉ ፖሊሲ መሠረት የኩባንያ ንብረት እና ገንዘብ ማስተናገድ ግዴታዎችን እተቀበላለሁ።</p>
<p>Date / ቀን: {$guarantor->created_at?->toDateString()}</p>
</body>
</html>
HTML;
    }
}
