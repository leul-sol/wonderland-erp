<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

class PdfExportService
{
    /**
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, mixed>  $meta
     */
    public function download(string $title, array $rows, string $filename, array $meta = []): Response
    {
        $letterheadPath = (string) config('finance.pdf_letterhead_path', '');
        $letterheadDataUri = null;

        if ($letterheadPath !== '' && is_readable($letterheadPath)) {
            $mime = str_ends_with(strtolower($letterheadPath), '.png') ? 'image/png' : 'image/jpeg';
            $letterheadDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($letterheadPath));
        }

        $periodLabel = null;
        if (! empty($meta['from']) && ! empty($meta['to'])) {
            $periodLabel = $meta['from'].' to '.$meta['to'];
        } elseif (! empty($meta['fiscal_period_id'])) {
            $periodLabel = 'fiscal period #'.$meta['fiscal_period_id'];
        }

        $html = view('exports.report', [
            'title' => $title,
            'rows' => $rows,
            'generatedAt' => now()->toIso8601String(),
            'generatedBy' => $meta['generated_by'] ?? null,
            'periodLabel' => $periodLabel,
            'reportSlug' => $meta['report_slug'] ?? null,
            'letterheadDataUri' => $letterheadDataUri,
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
