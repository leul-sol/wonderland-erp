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

class ReceivableController extends Controller
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
        $status = (string) $request->input('status', 'open');
        $agingBucket = $request->string('aging_bucket')->toString() ?: null;

        if (! in_array($status, ['open', 'partial', 'settled', 'written_off'], true)) {
            $status = 'open';
        }

        return Inertia::render('Finance/Receivables/Index', [
            'status' => $status,
            'agingBucket' => $agingBucket,
            'canSettle' => $this->auth->hasAnyPermission(['S4.finance.receivables.settle']),
            'receivables' => $this->deferApi(function () use ($status, $agingBucket) {
                $query = array_filter([
                    'aging_bucket' => $agingBucket,
                ], fn ($value) => $value !== null && $value !== '');

                $response = $this->s4->receivables($status === 'all' ? null : $status, $query);
                $receivables = $response['data'] ?? [];

                return is_array($receivables) ? $receivables : [];
            }),
        ]);
    }

    public function settle(Request $request, int $receivable): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank,pos,visa'],
        ]);

        try {
            $this->s4->settleReceivable($receivable, [
                'amount' => (float) $data['amount'],
                'payment_method' => $data['payment_method'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Receivable payment recorded.');
    }

    public function writeOff(Request $request, int $receivable): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s4->writeOffReceivable($receivable, [
                'reason' => $data['reason'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Receivable written off.');
    }
}
