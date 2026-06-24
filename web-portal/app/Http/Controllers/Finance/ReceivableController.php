<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
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
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s4->receivables('open');
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $receivables = $response['data'] ?? [];

        return Inertia::render('Finance/Receivables/Index', [
            'receivables' => is_array($receivables) ? $receivables : [],
            'canSettle' => $this->auth->hasAnyPermission(['S4.finance.receivables.settle']),
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
