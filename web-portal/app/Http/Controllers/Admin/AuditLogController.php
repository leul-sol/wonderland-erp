<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S1AdminClient $s1,
    ) {
    }

    public function index(Request $request): \Inertia\Response
    {
        $query = $this->filterQuery($request);

        try {
            $response = $this->s1->auditLogs($query);
        } catch (ApiException $e) {
            return \Inertia\Inertia::render('Admin/Audit/Index', [
                'auditLogs' => [],
                'meta' => null,
                'filters' => $this->filterProps($request),
                'exportQuery' => '',
                ...$this->apiLoadErrorProps($e),
            ]);
        }

        return \Inertia\Inertia::render('Admin/Audit/Index', [
            'auditLogs' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? null,
            'filters' => $this->filterProps($request),
            'exportQuery' => $this->exportQueryString($request),
            'loadError' => null,
            'loadErrorCode' => null,
        ]);
    }

    public function export(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $query = $this->filterQuery($request);
        $query['per_page'] = 100;

        try {
            $rows = $this->collectAuditRows($query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'admin.audit.index');
        }

        $filename = 'audit-log-'.now()->format('Y-m-d-His').'.csv';

        return Response::streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['id', 'created_at', 'event', 'user_id', 'username', 'ip_address', 'user_agent']);

            foreach ($rows as $log) {
                fputcsv($handle, [
                    $log['id'] ?? '',
                    $log['created_at'] ?? '',
                    $log['event'] ?? '',
                    $log['user_id'] ?? '',
                    $log['user']['username'] ?? '',
                    $log['ip_address'] ?? '',
                    $log['user_agent'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<array<string, mixed>>
     */
    private function collectAuditRows(array $query): array
    {
        $rows = [];
        $page = 1;
        $lastPage = 1;

        do {
            $response = $this->s1->auditLogs([...$query, 'page' => $page]);
            $batch = $response['data'] ?? [];
            $rows = array_merge($rows, is_array($batch) ? $batch : []);
            $lastPage = (int) ($response['meta']['last_page'] ?? 1);
            $page++;
        } while ($page <= $lastPage && count($rows) < 5000);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function filterQuery(Request $request): array
    {
        return array_filter([
            'event' => $request->input('event'),
            'user_id' => $request->input('user_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'page' => $request->input('page'),
            'per_page' => 25,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, string>
     */
    private function filterProps(Request $request): array
    {
        return [
            'event' => $request->input('event', ''),
            'user_id' => $request->input('user_id', ''),
            'from' => $request->input('from', ''),
            'to' => $request->input('to', ''),
        ];
    }

    private function exportQueryString(Request $request): string
    {
        return http_build_query(array_filter([
            'event' => $request->input('event'),
            'user_id' => $request->input('user_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== ''));
    }
}
