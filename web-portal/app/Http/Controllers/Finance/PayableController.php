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

class PayableController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Finance/Payables/Index', [
            'payables' => $this->deferApi(function () {
                $response = $this->s4->payables('open');
                $payables = $response['data'] ?? [];

                return is_array($payables) ? $payables : [];
            }),
        ]);
    }

    public function settle(Request $request, int $payable): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank'],
        ]);

        try {
            $this->s4->settlePayable($payable, [
                'amount' => (float) $data['amount'],
                'payment_method' => $data['payment_method'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Payable payment recorded.');
    }
}
