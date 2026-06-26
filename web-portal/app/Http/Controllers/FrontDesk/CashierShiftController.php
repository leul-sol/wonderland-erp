<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashierShiftController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->cashierShifts();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $payload = $response['data'] ?? [];
        $shifts = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);

        $openShift = collect($shifts)->firstWhere('status', 'open');

        return Inertia::render('FrontDesk/CashierShifts/Index', [
            'shifts' => array_values($shifts),
            'openShift' => $openShift,
        ]);
    }

    public function show(int $cashierShift): Response|RedirectResponse
    {
        try {
            $shiftResponse = $this->s3->cashierShift($cashierShift);
            $reportResponse = $this->s3->cashierShiftReport($cashierShift);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'front-desk.cashier-shifts.index');
        }

        return Inertia::render('FrontDesk/CashierShifts/Show', [
            'shift' => $shiftResponse['data'] ?? [],
            'report' => $reportResponse['data'] ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opening_cash_float' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $payload = [];
        if (isset($data['opening_cash_float']) && $data['opening_cash_float'] !== '') {
            $payload['opening_cash_float'] = (float) $data['opening_cash_float'];
        }

        try {
            $response = $this->s3->openCashierShift($payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $shiftId = (int) ($response['data']['id'] ?? 0);

        if ($shiftId <= 0) {
            return back()->with('error', 'Cashier shift was not opened.');
        }

        return redirect()
            ->route('front-desk.cashier-shifts.show', $shiftId)
            ->with('success', 'Cashier shift opened.');
    }

    public function close(Request $request, int $cashierShift): RedirectResponse
    {
        $data = $request->validate([
            'closing_cash_counted' => ['required', 'numeric', 'gte:0'],
        ]);

        try {
            $this->s3->closeCashierShift($cashierShift, [
                'closing_cash_counted' => (float) $data['closing_cash_counted'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('front-desk.cashier-shifts.show', $cashierShift)
            ->with('success', 'Cashier shift closed and reconciled.');
    }
}
