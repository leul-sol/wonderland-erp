<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\DiningTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiningTableController extends Controller
{
    use RespondsWithApiErrors;

    public function index(): JsonResponse
    {
        return response()->json(['data' => DiningTable::query()->where('is_active', true)->orderBy('table_number')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:60'],
        ]);

        $table = DiningTable::query()->create($data + ['is_active' => true]);

        return response()->json(['data' => $table], 201);
    }

    public function update(Request $request, DiningTable $diningTable): JsonResponse
    {
        $data = $request->validate([
            'table_number' => ['sometimes', 'string', 'max:10'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $diningTable->update($data);

        return response()->json(['data' => $diningTable->fresh()]);
    }
}
