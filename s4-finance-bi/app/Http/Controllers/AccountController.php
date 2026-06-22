<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
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
            'data' => $query->get()->map(fn (Account $account) => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
                'is_active' => $account->is_active,
            ])->values(),
        ]);
    }
}
