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

class AccountController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Finance/Accounts/Index', [
            'canCreate' => $this->auth->hasAnyPermission(['S4.finance.accounts.create']),
            'canUpdate' => $this->auth->hasAnyPermission(['S4.finance.accounts.update']),
            'accounts' => $this->deferApi(fn () => ($this->s4->accounts(['active_only' => false]))['data'] ?? []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
            'sub_type' => ['nullable', 'string', 'max:60'],
            'normal_balance' => ['required', 'in:debit,credit'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->s4->createAccount($data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Account created.');
    }

    public function update(Request $request, int $account): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:asset,liability,equity,income,expense'],
            'sub_type' => ['nullable', 'string', 'max:60'],
            'normal_balance' => ['sometimes', 'in:debit,credit'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->s4->updateAccount($account, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Account updated.');
    }
}
