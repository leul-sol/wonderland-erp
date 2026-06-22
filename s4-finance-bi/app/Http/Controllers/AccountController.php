<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesAccounts;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesAccounts;

    public function __construct(private readonly AccountService $accounts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Account::query()->orderBy('code');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($account) => $this->accountPayload($account))->values(),
        ]);
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        try {
            $account = $this->accounts->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->accountPayload($account)], 201);
    }

    public function show(Account $account): JsonResponse
    {
        return response()->json(['data' => $this->accountPayload($account)]);
    }

    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        try {
            $account = $this->accounts->update($account, $request->validated());
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->accountPayload($account)]);
    }
}
