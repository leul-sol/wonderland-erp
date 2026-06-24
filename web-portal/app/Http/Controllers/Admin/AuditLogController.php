<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S1AdminClient $s1,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $query = array_filter([
            'event' => $request->input('event'),
            'user_id' => $request->input('user_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'page' => $request->input('page'),
            'per_page' => 25,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->s1->auditLogs($query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Admin/Audit/Index', [
            'auditLogs' => $response['data'] ?? [],
            'meta' => $response['meta'] ?? null,
            'filters' => [
                'event' => $request->input('event', ''),
                'user_id' => $request->input('user_id', ''),
                'from' => $request->input('from', ''),
                'to' => $request->input('to', ''),
            ],
        ]);
    }
}
