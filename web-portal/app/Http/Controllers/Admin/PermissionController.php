<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S1AdminClient $s1,
    ) {
    }

    public function index(Request $request): Response
    {
        $domain = $request->string('domain')->toString() ?: null;
        $query = array_filter([
            'per_page' => 200,
            'page' => $request->input('page'),
        ], fn ($value) => $value !== null && $value !== '');

        return Inertia::render('Admin/Permissions/Index', [
            'domain' => $domain ?? '',
            'pageLoad' => $this->deferPageLoad(function () use ($domain, $query) {
                $allResponse = $this->s1->permissions(['per_page' => 200]);
                $domains = $this->extractDomains($allResponse['data'] ?? []);

                $response = $domain
                    ? $this->s1->permissionsByDomain($domain, $query)
                    : $allResponse;

                return [
                    'permissions' => $response['data'] ?? [],
                    'meta' => $response['meta'] ?? null,
                    'domains' => $domains,
                ];
            }),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $permissions
     * @return list<string>
     */
    private function extractDomains(array $permissions): array
    {
        return collect($permissions)
            ->pluck('domain')
            ->filter(fn ($domain) => is_string($domain) && $domain !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
