<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiReportController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function index(Request $request): Response
    {
        $category = $request->string('category')->toString() ?: null;

        return Inertia::render('Finance/BiReports/Index', [
            'category' => $category,
            'catalog' => $this->deferApi(function () use ($category) {
                $response = $this->s4->biReportCatalog($category);

                return $response['data'] ?? [];
            }),
        ]);
    }

    public function show(Request $request, string $slug): Response|RedirectResponse
    {
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->s4->biReport($slug, $query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'finance.bi-reports.index');
        }

        return Inertia::render('Finance/BiReports/Show', [
            'slug' => $slug,
            'filters' => $query,
            'report' => $response['data'] ?? [],
            'canExport' => true,
        ]);
    }

    public function export(Request $request, string $slug): StreamedResponse|RedirectResponse
    {
        $data = $request->validate([
            'format' => ['required', 'in:csv,pdf,excel'],
            'fiscal_period_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'integer'],
            'payroll_run_id' => ['nullable', 'integer'],
            'guarantor_id' => ['nullable', 'integer'],
        ]);

        $query = array_filter([
            'fiscal_period_id' => $data['fiscal_period_id'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'employee_id' => $data['employee_id'] ?? null,
            'payroll_run_id' => $data['payroll_run_id'] ?? null,
            'guarantor_id' => $data['guarantor_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->s4->downloadBiReport($slug, $data['format'], $query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'finance.bi-reports.show', ['slug' => $slug]);
        }

        $filename = $slug.'.'.$data['format'];
        $disposition = $response->header('Content-Disposition') ?: 'attachment; filename="'.$filename.'"';

        return response()->streamDownload(function () use ($response): void {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ]);
    }
}
