<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::query()->with('ingredients')->orderBy('code');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => $query->get()->map(fn ($item) => $this->menuItemPayload($item))->values(),
        ]);
    }
}
