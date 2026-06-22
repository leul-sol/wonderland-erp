<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\StoreBudgetLineRequest;
use App\Models\BudgetLine;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BudgetController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly BudgetService $budgets)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = BudgetLine::query()->orderBy('fiscal_period_id')->orderBy('account_code');

        if ($request->filled('fiscal_period_id')) {
            $query->where('fiscal_period_id', (int) $request->input('fiscal_period_id'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (BudgetLine $line) => [
                'id' => $line->id,
                'fiscal_period_id' => $line->fiscal_period_id,
                'account_code' => $line->account_code,
                'budget_amount' => (string) $line->budget_amount,
            ])->values(),
        ]);
    }

    public function store(StoreBudgetLineRequest $request): JsonResponse
    {
        try {
            $this->budgets->assertValidAccountCode($request->validated('account_code'));
            $line = $this->budgets->upsertLine($request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => [
            'id' => $line->id,
            'fiscal_period_id' => $line->fiscal_period_id,
            'account_code' => $line->account_code,
            'budget_amount' => (string) $line->budget_amount,
        ]], 201);
    }
}
