<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->paginatePermissions(
            Permission::query()->orderBy('action'),
            $request,
        ));
    }

    public function byDomain(Request $request, string $domain): JsonResponse
    {
        return response()->json($this->paginatePermissions(
            Permission::query()->where('domain', $domain)->orderBy('action'),
            $request,
        ));
    }

    private function paginatePermissions($query, Request $request): array
    {
        $paginator = $query->paginate(min((int) $request->input('per_page', 50), 200));

        return [
            'data' => $paginator->getCollection()->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'domain' => $permission->domain,
                'action' => $permission->action,
                'display_name' => $permission->display_name,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
