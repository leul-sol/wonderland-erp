<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait RespondsWithReportExport
{
    protected function respondWithReport(
        Request $request,
        ExportService $exports,
        string $reportSlug,
        array $data,
        ?int $fiscalPeriodId,
        ?string $from,
        ?string $to,
    ): JsonResponse|StreamedResponse|\Illuminate\Http\Response {
        $export = $request->query('export');

        if ($export === null || $export === '') {
            return response()->json(['data' => $data]);
        }

        if (! in_array($export, ['pdf', 'excel', 'csv'], true)) {
            return $this->error('VALIDATION_ERROR', 'export must be pdf, excel, or csv.', 422);
        }

        try {
            return $exports->export($reportSlug, $export, $fiscalPeriodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
