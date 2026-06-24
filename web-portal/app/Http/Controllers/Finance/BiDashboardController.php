<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiDashboardController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function executive(Request $request): Response|RedirectResponse
    {
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->s4->executiveDashboard($query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Finance/Dashboard/Executive', [
            'dashboard' => $response['data'] ?? [],
        ]);
    }

    public function operations(Request $request): Response|RedirectResponse
    {
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = $this->s4->operationsDashboard($query);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Finance/Dashboard/Operations', [
            'dashboard' => $response['data'] ?? [],
        ]);
    }
}
