<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class PdfExportService
{
    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function download(string $title, array $rows, string $filename): Response
    {
        $html = view('exports.report', [
            'title' => $title,
            'rows' => $rows,
            'generatedAt' => now()->toIso8601String(),
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
