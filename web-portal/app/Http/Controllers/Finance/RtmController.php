<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RtmController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $system = $request->string('system')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;

        return Inertia::render('Finance/Rtm/Index', [
            'filters' => ['system' => $system, 'status' => $status],
            'canUpdate' => $this->auth->hasAnyPermission(['S4.bi.rtm.update']),
            'rtm' => $this->deferApi(function () use ($system, $status) {
                $query = array_filter([
                    'system' => $system,
                    'status' => $status,
                ], fn ($value) => $value !== null && $value !== '');

                $response = $this->s4->rtmEntries($query);

                return [
                    'entries' => $response['data'] ?? [],
                    'meta' => $response['meta'] ?? [],
                ];
            }),
        ]);
    }

    public function update(Request $request, int $rtmEntry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:planned,in_progress,implemented,verified,deferred'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->s4->updateRtmEntry($rtmEntry, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'RTM entry updated.');
    }
}
