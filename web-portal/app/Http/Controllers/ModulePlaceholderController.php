<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModulePlaceholderController extends Controller
{
    public function __invoke(Request $request, string $module): Response
    {
        $config = collect(config('portal.modules', []))
            ->firstWhere('key', $module);

        abort_if($config === null, 404);

        $permissions = is_array($config['permissions'] ?? null) ? $config['permissions'] : [];
        abort_unless(app(\App\Services\Auth\PortalAuthService::class)->hasAnyPermission($permissions), 403);

        return Inertia::render('Modules/Placeholder', [
            'moduleKey' => $module,
            'moduleLabel' => (string) ($config['label'] ?? $module),
            'phase' => (int) ($config['phase'] ?? 0),
        ]);
    }
}
