<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\ExportReportRequest;
use App\Services\ExportService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly ExportService $exports)
    {
    }

    public function store(ExportReportRequest $request): StreamedResponse|Response|\Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user', []);
            $generatedBy = (string) ($user['username'] ?? $user['email'] ?? ('User #'.($user['sub'] ?? 'unknown')));

            return $this->exports->export(
                $request->validated('report'),
                $request->validated('format'),
                $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
                $request->input('from'),
                $request->input('to'),
                $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
                $request->filled('payroll_run_id') ? (int) $request->input('payroll_run_id') : null,
                $request->filled('guarantor_id') ? (int) $request->input('guarantor_id') : null,
                $generatedBy,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }
}
