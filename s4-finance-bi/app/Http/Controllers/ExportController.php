<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\ExportReportRequest;
use App\Services\ExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly ExportService $exports)
    {
    }

    public function store(ExportReportRequest $request): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            return $this->exports->export(
                $request->validated('report'),
                $request->validated('format'),
                $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
                $request->input('from'),
                $request->input('to'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
